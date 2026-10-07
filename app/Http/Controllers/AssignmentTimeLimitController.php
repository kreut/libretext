<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\Exceptions\Handler;
use App\Helpers\Helper;
use App\Traits\DateFormatter;
use App\User;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class AssignmentTimeLimitController extends Controller
{
    use DateFormatter;

    /**
     * Value the Overrides page sends for "Everybody" - same convention as
     * SubmissionOverrideController.
     */
    private const EVERYBODY = -1;

    /**
     * Student-facing: tells the frontend whether to show a "Start" button
     * or a running countdown for the current user.
     *
     * @param Assignment $assignment
     * @return array
     */
    public function getStatus(Assignment $assignment): array
    {
        $response['type'] = 'error';
        try {
            $authorized = Gate::inspect('view', $assignment);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            $user = Auth::user();
            $response = $this->buildTimeLimitStatus($assignment, $user);
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = 'There was an error getting the time limit status. Please try again or contact us for assistance.';
        }
        return $response;
    }

    /**
     * Instructor-facing: status for one student, or a class-wide summary
     * when "Everybody" (-1) is chosen.
     *
     * @param Request $request
     * @param Assignment $assignment
     * @param $user
     * @return array
     */
    public function getStatusForStudent(Request $request, Assignment $assignment, $user): array
    {
        $response['type'] = 'error';
        try {
            $authorized = Gate::inspect('manageAssignmentTimeLimit', [$assignment]);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            if ($this->isEverybody($user)) {
                return $this->buildEverybodySummary($request, $assignment);
            }
            $student = User::find($user);
            if (!$student) {
                $response['message'] = 'We could not find that student.';
                return $response;
            }
            $response = $this->buildTimeLimitStatus($assignment, $student);
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = 'There was an error getting the time limit status for this student. Please try again or contact us for assistance.';
        }
        return $response;
    }

    /**
     * Shared by getStatus (self) and getStatusForStudent (instructor, on
     * behalf of a student) so the two never drift out of sync with each
     * other's response shape.
     *
     * @param Assignment $assignment
     * @param User $user
     * @return array
     */
    private function buildTimeLimitStatus(Assignment $assignment, User $user): array
    {
        $response['type'] = 'error';
        $assign_to_timing = $assignment->assignToTimingByUser('', $user->id);
        if (!$assign_to_timing || !$assign_to_timing->time_limit) {
            $response['type'] = 'success';
            $response['time_limit'] = null;
            return $response;
        }

        $timing_start = $this->getTimingStart($assign_to_timing->id, $user->id);

        $response['type'] = 'success';
        $response['time_limit'] = $assign_to_timing->time_limit;
        $response['due'] = $assign_to_timing->due;
        $start = Carbon::now();
        $response['time_limit_seconds'] = $start->copy()->add(CarbonInterval::make($assign_to_timing->time_limit))->getTimestamp() - $start->getTimestamp();
        $response['started_at'] = ($timing_start && $timing_start->started_at)
            ? $this->convertUTCMysqlFormattedDateToLocalDateAndTime($timing_start->started_at, Auth::user()->time_zone)
            : null;
        $response['expires_at'] = ($timing_start && $timing_start->expires_at)
            ? $this->convertUTCMysqlFormattedDateToLocalDateAndTime($timing_start->expires_at, Auth::user()->time_zone)
            : null;
        $response['extended_past_due'] = (bool)($timing_start->extended_past_due ?? false);
        $response['time_limit_override'] = $timing_start->time_limit_override ?? null;
        $response['instructor_adjusted'] = (bool)($timing_start->instructor_adjusted ?? false);
        $response['seconds_left'] = ($timing_start && $timing_start->expires_at)
            ? max(0, strtotime($timing_start->expires_at) - time())
            : null;
        return $response;
    }

    /**
     * Class-wide summary for "Everybody": how many students are running,
     * expired, or not started.
     *
     * @param Request $request
     * @param Assignment $assignment
     * @return array
     */
    private function buildEverybodySummary(Request $request, Assignment $assignment): array
    {
        $summary = ['total' => 0, 'running' => 0, 'expired' => 0, 'not_started' => 0];
        $time_limits = [];
        foreach ($this->everybodyStudentIds($request, $assignment) as $user_id) {
            $assign_to_timing = $assignment->assignToTimingByUser('', $user_id);
            if (!$assign_to_timing || !$assign_to_timing->time_limit) {
                continue;
            }
            $summary['total']++;
            $time_limits[$assign_to_timing->time_limit] = true;
            $timing_start = $this->getTimingStart($assign_to_timing->id, $user_id);
            if (!$timing_start || !$timing_start->started_at) {
                $summary['not_started']++;
            } elseif (strtotime($timing_start->expires_at) > time()) {
                $summary['running']++;
            } else {
                $summary['expired']++;
            }
        }
        return [
            'type' => 'success',
            'is_everybody' => true,
            'time_limit' => $summary['total'] ? implode(', ', array_keys($time_limits)) : null,
            'summary' => $summary,
        ];
    }

    /**
     * Student-facing: starts the student's personal clock. Idempotent -
     * calling it again after a clock is already running just returns the
     * existing expiry rather than restarting it.
     *
     * @param Assignment $assignment
     * @return array
     */
    public function start(Assignment $assignment): array
    {
        $response['type'] = 'error';
        try {
            $authorized = Gate::inspect('view', $assignment);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            $user = Auth::user();
            $assign_to_timing = $assignment->assignToTimingByUser('', $user->id);
            if (!$assign_to_timing) {
                $response['message'] = 'You are not assigned to this assignment.';
                return $response;
            }
            if (!$assign_to_timing->time_limit) {
                $response['message'] = 'This assignment does not have a time limit.';
                return $response;
            }
            if (strtotime($assign_to_timing->available_from) > time()) {
                $response['message'] = 'This assignment is not yet available.';
                return $response;
            }
            if (strtotime($assign_to_timing->due) < time() && !$user->fake_student) {
                $response['message'] = 'This assignment is past due and can no longer be started.';
                return $response;
            }

            $existing = $this->getTimingStart($assign_to_timing->id, $user->id);

            if ($existing && $existing->started_at) {
                // Already started - hand back the existing expiry rather than
                // resetting the clock (that's what the instructor add/set
                // endpoints below are for).
                $response['type'] = 'success';
                $response['expires_at'] = $this->convertUTCMysqlFormattedDateToLocalDateAndTime($existing->expires_at, Auth::user()->time_zone);
                $response['seconds_left'] = max(0, strtotime($existing->expires_at) - time());
                return $response;
            }

            $now = Carbon::now();
            $interval = CarbonInterval::make($assign_to_timing->time_limit);
            $expires_at = $now->copy()->add($interval);

            // Automatic starts always cap at the group's due date - only an
            // explicit instructor add/set action (below) can push a student's
            // personal expiry past due. Fake students are exempt.
            $due = Carbon::parse($assign_to_timing->due);
            if ($expires_at->greaterThan($due) && !$user->fake_student) {
                $expires_at = $due;
            }

            DB::table('assign_to_timing_starts')->updateOrInsert(
                ['assign_to_timing_id' => $assign_to_timing->id, 'user_id' => $user->id],
                ['started_at' => $now, 'expires_at' => $expires_at, 'updated_at' => $now, 'created_at' => $now]
            );

            $response['type'] = 'success';
            $response['expires_at'] = $this->convertUTCMysqlFormattedDateToLocalDateAndTime($expires_at->toDateTimeString(), Auth::user()->time_zone);
            $response['seconds_left'] = max(0, $expires_at->getTimestamp() - time());
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = 'There was an error starting the timer for this assignment. Please try again or contact us for assistance.';
        }
        return $response;
    }

    /**
     * Instructor-facing: add minutes to one student's clock, or (Everybody)
     * to every student whose clock is currently running. For Everybody,
     * expired and not-started students are skipped so nobody is revived
     * by accident.
     *
     * @param Request $request
     * @param Assignment $assignment
     * @param $user
     * @return array
     */
    public function addTime(Request $request, Assignment $assignment, $user): array
    {
        $response['type'] = 'error';
        try {
            $authorized = Gate::inspect('manageAssignmentTimeLimit', [$assignment]);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }

            $minutes_to_add = (int)$request->minutes;
            if ($minutes_to_add < 1) {
                $response['message'] = 'Please enter a positive number of minutes.';
                return $response;
            }

            if ($this->isEverybody($user)) {
                $counts = ['updated' => 0, 'expired' => 0, 'not_started' => 0, 'extended_past_due' => 0];
                $to_publish = [];
                DB::beginTransaction();
                foreach ($this->everybodyStudentIds($request, $assignment) as $user_id) {
                    $result = $this->addTimeForUser($assignment, $user_id, $minutes_to_add, false);
                    if ($result['status'] === 'updated') {
                        $counts['updated']++;
                        if ($result['extended_past_due']) {
                            $counts['extended_past_due']++;
                        }
                        $to_publish[$user_id] = $result['publish'];
                    } elseif (isset($counts[$result['status']])) {
                        $counts[$result['status']]++;
                    }
                }
                DB::commit();
                $this->publishAll($assignment, $to_publish);

                $parts = ["Added $minutes_to_add minute(s) for {$counts['updated']} student(s) with a running clock."];
                if ($counts['expired']) {
                    $parts[] = "{$counts['expired']} student(s) whose time had expired were not changed.";
                }
                if ($counts['not_started']) {
                    $parts[] = "{$counts['not_started']} student(s) who haven't started were not changed.";
                }
                if ($counts['extended_past_due']) {
                    $parts[] = "Note: {$counts['extended_past_due']} student(s) now have time extending past the due date.";
                }
                $response['type'] = 'success';
                $response['is_everybody'] = true;
                $response['counts'] = $counts;
                $response['extended_past_due'] = $counts['extended_past_due'] > 0;
                $response['message'] = implode(' ', $parts);
                return $response;
            }

            $student = User::find($user);
            if (!$student) {
                $response['message'] = 'We could not find that student.';
                return $response;
            }
            $result = $this->addTimeForUser($assignment, $student->id, $minutes_to_add, true);
            if ($result['status'] === 'no_time_limit') {
                $response['message'] = 'This student does not have a time limit set for this assignment.';
                return $response;
            }
            if ($result['status'] === 'not_started') {
                $response['message'] = 'This student has not started the assignment yet, so there is no clock to add time to.';
                return $response;
            }

            $response['type'] = 'success';
            $response['expires_at'] = $this->convertUTCMysqlFormattedDateToLocalDateAndTime($result['expires_at']->toDateTimeString(), Auth::user()->time_zone);
            $response['seconds_left'] = $result['publish']['seconds_left'];
            $response['was_expired'] = $result['was_expired'];
            $response['extended_past_due'] = $result['extended_past_due'];
            $this->publishTimeLimitUpdate($assignment, $student->id, $result['publish']);
        } catch (Exception $e) {
            if (DB::transactionLevel()) {
                DB::rollBack();
            }
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = 'There was an error adding time. Please try again or contact us for assistance.';
        }
        return $response;
    }

    /**
     * Adds minutes to one student's clock. Does not publish - the caller
     * does that (after commit, for bulk).
     *
     * @param Assignment $assignment
     * @param int $user_id
     * @param int $minutes
     * @param bool $revive_expired If false, expired clocks are left alone.
     * @return array
     */
    private function addTimeForUser(Assignment $assignment, int $user_id, int $minutes, bool $revive_expired): array
    {
        $assign_to_timing = $assignment->assignToTimingByUser('', $user_id);
        if (!$assign_to_timing || !$assign_to_timing->time_limit) {
            return ['status' => 'no_time_limit'];
        }
        $timing_start = $this->getTimingStart($assign_to_timing->id, $user_id);
        if (!$timing_start || !$timing_start->started_at) {
            return ['status' => 'not_started'];
        }

        $current_expires_at = Carbon::parse($timing_start->expires_at);
        // If already expired, "add time" only means something if it's
        // measured from now.
        $was_expired = $current_expires_at->isPast();
        if ($was_expired && !$revive_expired) {
            return ['status' => 'expired'];
        }
        $base = $was_expired ? Carbon::now() : $current_expires_at;
        $new_expires_at = $base->copy()->addMinutes($minutes);
        $extended_past_due = $new_expires_at->greaterThan(Carbon::parse($assign_to_timing->due));

        DB::table('assign_to_timing_starts')
            ->where('id', $timing_start->id)
            ->update([
                'expires_at' => $new_expires_at,
                'extended_past_due' => $extended_past_due,
                'instructor_adjusted' => true,
                'updated_at' => Carbon::now(),
            ]);

        return [
            'status' => 'updated',
            'expires_at' => $new_expires_at,
            'was_expired' => $was_expired,
            'extended_past_due' => $extended_past_due,
            'publish' => [
                'seconds_left' => max(0, $new_expires_at->getTimestamp() - time()),
                'extended_past_due' => $extended_past_due,
            ],
        ];
    }

    /**
     * Instructor-facing: set a new total duration.
     * - One student: same as before (running clocks are measured from
     *   started_at; never-started or expired clocks get a fresh window now).
     * - Everybody: applies to every student who has started, always measured
     *   from their own started_at. Students who haven't started are skipped -
     *   they get the assignment's normal time limit when they click Start.
     *
     * @param Request $request
     * @param Assignment $assignment
     * @param $user
     * @return array
     */
    public function setTime(Request $request, Assignment $assignment, $user): array
    {
        $response['type'] = 'error';
        try {
            $authorized = Gate::inspect('manageAssignmentTimeLimit', [$assignment]);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }

            try {
                $interval = CarbonInterval::make($request->time_limit);
            } catch (Exception $e) {
                $interval = null;
            }
            if (!$interval) {
                $response['message'] = 'That is not a valid duration.';
                return $response;
            }

            if ($this->isEverybody($user)) {
                $counts = ['updated' => 0, 'still_expired' => 0, 'not_started' => 0, 'extended_past_due' => 0];
                $to_publish = [];
                DB::beginTransaction();
                foreach ($this->everybodyStudentIds($request, $assignment) as $user_id) {
                    $result = $this->setTimeForUser($assignment, $user_id, $interval, $request->time_limit, true);
                    if ($result['status'] === 'updated') {
                        $counts['updated']++;
                        if ($result['expires_at']->isPast()) {
                            $counts['still_expired']++;
                        }
                        if ($result['extended_past_due']) {
                            $counts['extended_past_due']++;
                        }
                        $to_publish[$user_id] = $result['publish'];
                    } elseif ($result['status'] === 'not_started') {
                        $counts['not_started']++;
                    }
                }
                DB::commit();
                $this->publishAll($assignment, $to_publish);

                $parts = ["Set a total duration of $request->time_limit for {$counts['updated']} student(s)."];
                if ($counts['still_expired']) {
                    $parts[] = "{$counts['still_expired']} of them are still expired because that much time has already passed since they started.";
                }
                if ($counts['not_started']) {
                    $parts[] = "{$counts['not_started']} student(s) who haven't started were not changed.";
                }
                if ($counts['extended_past_due']) {
                    $parts[] = "Note: {$counts['extended_past_due']} student(s) now have time extending past the due date.";
                }
                $response['type'] = 'success';
                $response['is_everybody'] = true;
                $response['counts'] = $counts;
                $response['extended_past_due'] = $counts['extended_past_due'] > 0;
                $response['message'] = implode(' ', $parts);
                return $response;
            }

            $student = User::find($user);
            if (!$student) {
                $response['message'] = 'We could not find that student.';
                return $response;
            }
            $result = $this->setTimeForUser($assignment, $student->id, $interval, $request->time_limit, false);
            if ($result['status'] === 'no_time_limit') {
                $response['message'] = 'This student does not have a time limit set for this assignment.';
                return $response;
            }

            $response['type'] = 'success';
            $response['expires_at'] = $this->convertUTCMysqlFormattedDateToLocalDateAndTime($result['expires_at']->toDateTimeString(), Auth::user()->time_zone);
            $response['seconds_left'] = $result['publish']['seconds_left'];
            $response['was_expired'] = $result['was_expired'];
            $response['extended_past_due'] = $result['extended_past_due'];
            $this->publishTimeLimitUpdate($assignment, $student->id, $result['publish']);
        } catch (Exception $e) {
            if (DB::transactionLevel()) {
                DB::rollBack();
            }
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = 'There was an error setting the time limit. Please try again or contact us for assistance.';
        }
        return $response;
    }

    /**
     * Sets a new total duration for one student. Does not publish.
     *
     * @param Assignment $assignment
     * @param int $user_id
     * @param CarbonInterval $interval
     * @param string $time_limit_text
     * @param bool $bulk
     * @return array
     */
    private function setTimeForUser(Assignment $assignment, int $user_id, CarbonInterval $interval, string $time_limit_text, bool $bulk): array
    {
        $assign_to_timing = $assignment->assignToTimingByUser('', $user_id);
        if (!$assign_to_timing || !$assign_to_timing->time_limit) {
            return ['status' => 'no_time_limit'];
        }
        $timing_start = $this->getTimingStart($assign_to_timing->id, $user_id);
        $has_started = $timing_start && $timing_start->started_at;
        if ($bulk && !$has_started) {
            return ['status' => 'not_started'];
        }

        $was_expired = $has_started && Carbon::parse($timing_start->expires_at)->isPast();
        $is_still_running = $has_started && !$was_expired;
        // Single student: never-started or expired gets a fresh window from
        // now. Bulk: always measured from each student's own start.
        $started_at = ($bulk || $is_still_running) ? Carbon::parse($timing_start->started_at) : Carbon::now();
        $new_expires_at = $started_at->copy()->add($interval);
        $extended_past_due = $new_expires_at->greaterThan(Carbon::parse($assign_to_timing->due));

        DB::table('assign_to_timing_starts')->updateOrInsert(
            ['assign_to_timing_id' => $assign_to_timing->id, 'user_id' => $user_id],
            [
                'started_at' => $started_at,
                'expires_at' => $new_expires_at,
                'time_limit_override' => $time_limit_text,
                'extended_past_due' => $extended_past_due,
                'instructor_adjusted' => true,
                'updated_at' => Carbon::now(),
            ]
        );

        return [
            'status' => 'updated',
            'expires_at' => $new_expires_at,
            'was_expired' => $was_expired,
            'extended_past_due' => $extended_past_due,
            'publish' => [
                'seconds_left' => max(0, $new_expires_at->getTimestamp() - time()),
                'extended_past_due' => $extended_past_due,
            ],
        ];
    }

    /**
     * Fake-student / instructor-testing only: deletes the current user's own
     * timing-start row so they can click "Start Assignment" again.
     *
     * @param Assignment $assignment
     * @return array
     */
    public function resetTimer(Assignment $assignment): array
    {
        $response['type'] = 'error';
        try {
            $authorized = Gate::inspect('resetOwnTimeLimit', [$assignment]);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            $user = Auth::user();
            $assign_to_timing = $assignment->assignToTimingByUser('', $user->id);
            if (!$assign_to_timing) {
                $response['message'] = 'You are not assigned to this assignment.';
                return $response;
            }

            DB::table('assign_to_timing_starts')
                ->where('assign_to_timing_id', $assign_to_timing->id)
                ->where('user_id', $user->id)
                ->delete();

            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = 'There was an error resetting the timer. Please try again or contact us for assistance.';
        }
        return $response;
    }

    /**
     * Instructor-facing: fully resets one student's clock, or every
     * student's clock for Everybody.
     *
     * @param Request $request
     * @param Assignment $assignment
     * @param $user
     * @return array
     */
    public function resetTimerForStudent(Request $request, Assignment $assignment, $user): array
    {
        $response['type'] = 'error';
        try {
            $authorized = Gate::inspect('manageAssignmentTimeLimit', [$assignment]);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }

            if ($this->isEverybody($user)) {
                $reset_user_ids = [];
                DB::beginTransaction();
                foreach ($this->everybodyStudentIds($request, $assignment) as $user_id) {
                    $assign_to_timing = $assignment->assignToTimingByUser('', $user_id);
                    if (!$assign_to_timing) {
                        continue;
                    }
                    $deleted = DB::table('assign_to_timing_starts')
                        ->where('assign_to_timing_id', $assign_to_timing->id)
                        ->where('user_id', $user_id)
                        ->delete();
                    if ($deleted) {
                        $reset_user_ids[] = $user_id;
                    }
                }
                DB::commit();
                foreach ($reset_user_ids as $user_id) {
                    $this->publishTimeLimitUpdate($assignment, $user_id, ['seconds_left' => null]);
                }
                $response['type'] = 'success';
                $response['is_everybody'] = true;
                $response['message'] = 'Timers reset for ' . count($reset_user_ids) . ' student(s).';
                return $response;
            }

            $student = User::find($user);
            if (!$student) {
                $response['message'] = 'We could not find that student.';
                return $response;
            }
            $assign_to_timing = $assignment->assignToTimingByUser('', $student->id);
            if (!$assign_to_timing) {
                $response['message'] = 'This student is not assigned to this assignment.';
                return $response;
            }

            DB::table('assign_to_timing_starts')
                ->where('assign_to_timing_id', $assign_to_timing->id)
                ->where('user_id', $student->id)
                ->delete();

            $response['type'] = 'success';
            $this->publishTimeLimitUpdate($assignment, $student->id, ['seconds_left' => null]);
        } catch (Exception $e) {
            if (DB::transactionLevel()) {
                DB::rollBack();
            }
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = 'There was an error resetting the timer. Please try again or contact us for assistance.';
        }
        return $response;
    }

    /**
     * Instructor-facing: lists every real student who has started their
     * personal timer for this assignment, across all assign-to groups.
     *
     * @param Assignment $assignment
     * @return array
     */
    public function getAllStatuses(Assignment $assignment): array
    {
        $response['type'] = 'error';
        try {
            $authorized = Gate::inspect('manageAssignmentTimeLimit', [$assignment]);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }

            $assign_to_timing_ids = DB::table('assign_to_timings')
                ->where('assignment_id', $assignment->id)
                ->pluck('id');

            $rows = DB::table('assign_to_timing_starts')
                ->join('users', 'assign_to_timing_starts.user_id', '=', 'users.id')
                ->join('assign_to_timings', 'assign_to_timing_starts.assign_to_timing_id', '=', 'assign_to_timings.id')
                ->whereIn('assign_to_timing_starts.assign_to_timing_id', $assign_to_timing_ids)
                ->where('users.fake_student', 0)
                ->orderBy('users.last_name')
                ->orderBy('users.first_name')
                ->select(
                    'users.id AS user_id',
                    DB::raw("CONCAT(users.first_name, ' ', users.last_name) AS name"),
                    'assign_to_timings.time_limit',
                    'assign_to_timing_starts.started_at',
                    'assign_to_timing_starts.expires_at',
                    'assign_to_timing_starts.extended_past_due',
                    'assign_to_timing_starts.time_limit_override',
                    'assign_to_timing_starts.instructor_adjusted'
                )
                ->get();

            $statuses = [];
            foreach ($rows as $row) {
                $statuses[] = [
                    'user_id' => $row->user_id,
                    'name' => $row->name,
                    'time_limit' => $row->time_limit_override ?: $row->time_limit,
                    'is_override' => (bool)$row->time_limit_override,
                    'started_at' => $this->convertUTCMysqlFormattedDateToLocalDateAndTime($row->started_at, Auth::user()->time_zone),
                    'expires_at' => $this->convertUTCMysqlFormattedDateToLocalDateAndTime($row->expires_at, Auth::user()->time_zone),
                    'seconds_left' => max(0, strtotime($row->expires_at) - time()),
                    'extended_past_due' => (bool)$row->extended_past_due,
                    'instructor_adjusted' => (bool)$row->instructor_adjusted,
                ];
            }

            $response['type'] = 'success';
            $response['statuses'] = $statuses;
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = 'There was an error getting the timer statuses for this assignment. Please try again or contact us for assistance.';
        }
        return $response;
    }

    private function isEverybody($user): bool
    {
        return (int)$user === self::EVERYBODY;
    }

    /**
     * Real (non-fake) enrolled students - same source SubmissionOverrideController
     * uses for "Everybody".
     */
    private function everybodyStudentIds(Request $request, Assignment $assignment): array
    {
        $enrolled_ids = $assignment->getEnrolledStudentIdsByAssignment($request->user()->role);
        return User::whereIn('id', $enrolled_ids)
            ->where('fake_student', 0)
            ->pluck('id')
            ->toArray();
    }

    private function getTimingStart(int $assign_to_timing_id, int $user_id)
    {
        return DB::table('assign_to_timing_starts')
            ->where('assign_to_timing_id', $assign_to_timing_id)
            ->where('user_id', $user_id)
            ->first();
    }

    private function publishAll(Assignment $assignment, array $to_publish): void
    {
        foreach ($to_publish as $user_id => $data) {
            $this->publishTimeLimitUpdate($assignment, $user_id, $data);
        }
    }

    /**
     * Pushes a live update to the affected student's own assignment page via
     * Centrifugo. Swallows its own failures: the DB change already succeeded.
     */
    private function publishTimeLimitUpdate(Assignment $assignment, int $user_id, array $data): void
    {
        try {
            $client = Helper::centrifuge();
            $client->publish("time-limit-update-$assignment->id", array_merge([
                'assignment_id' => $assignment->id,
                'user_id' => $user_id,
            ], $data));
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
        }
    }
}
