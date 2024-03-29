<?php

namespace App;

use App\Exceptions\Handler;
use App\Helpers\Helper;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Traits\DateFormatter;

class Assignment extends Model
{
    use DateFormatter;

    protected $guarded = [];

    public function removeAllAssociatedInformation(AssignToTiming $assignToTiming)
    {
        $assignment_question_ids = DB::table('assignment_question')
            ->where('assignment_id', $this->id)
            ->get()
            ->pluck('id');

        DB::table('assignment_question_learning_tree')
            ->whereIn('assignment_question_id', $assignment_question_ids)
            ->delete();

        DB::table('assignment_question')->where('assignment_id', $this->id)->delete();
        DB::table('extensions')->where('assignment_id', $this->id)->delete();
        DB::table('scores')->where('assignment_id', $this->id)->delete();
        DB::table('submission_files')->where('assignment_id', $this->id)->delete();
        DB::table('submissions')->where('assignment_id', $this->id)->delete();
        DB::table('can_give_ups')->where('assignment_id', $this->id)->delete();
        DB::table('seeds')->where('assignment_id', $this->id)->delete();
        DB::table('cutups')->where('assignment_id', $this->id)->delete();
        DB::table('lti_launches')->where('assignment_id', $this->id)->delete();
        DB::table('randomized_assignment_questions')->where('assignment_id', $this->id)->delete();
        DB::table('compiled_pdf_overrides')->where('assignment_id', $this->id)->delete();
        DB::table('question_level_overrides')->where('assignment_id', $this->id)->delete();
        DB::table('assignment_level_overrides')->where('assignment_id', $this->id)->delete();

        DB::table('learning_tree_successful_branches')->where('assignment_id', $this->id)->delete();
        DB::table('learning_tree_time_lefts')->where('assignment_id', $this->id)->delete();
        DB::table('remediation_submissions')->where('assignment_id', $this->id)->delete();
        DB::table('assignment_question_time_on_tasks')->where('assignment_id', $this->id)->delete();
        DB::table('review_histories')->where('assignment_id', $this->id)->delete();
        DB::table('shown_hints')->where('assignment_id', $this->id)->delete();
        DB::table('assignment_topics')->where('assignment_id', $this->id)->delete();
        DB::table('submission_confirmations')->where('assignment_id', $this->id)->delete();
        $this->graders()->detach();
        $assignToTiming->deleteTimingsGroupsUsers($this);

        $course = $this->course;
        $number_with_the_same_assignment_group_weight = DB::table('assignments')
            ->where('course_id', $course->id)
            ->where('assignment_group_id', $this->assignment_group_id)
            ->select()
            ->get();
        if (count($number_with_the_same_assignment_group_weight) === 1) {
            DB::table('assignment_group_weights')
                ->where('course_id', $course->id)
                ->where('assignment_group_id', $this->assignment_group_id)
                ->delete();
        }
        $assignments = $course->assignments->where('id', '<>', $this->id)
            ->pluck('id')
            ->toArray();
        $this->orderAssignments($assignments, $course);
        $this->delete();
    }

    /**
     * @param $assignments
     * @param array $assignment_ids
     * @return array
     */
    public function getTotalPointsByAssignmentId($assignments, array $assignment_ids): array
    {


        foreach ($assignments as $assignment) {
            if ($assignment->number_of_randomized_assessments) {
                $randomized_assignment_total_points[$assignment->id] = $assignment->default_points_per_question * $assignment->number_of_randomized_assessments;
            }
        }
        $total_points_by_assignment_id = [];
        $adapt_total_points = DB::table('assignment_question')
            ->selectRaw('assignment_id, sum(points) as sum')
            ->whereIn('assignment_id', $assignment_ids)
            ->groupBy('assignment_id')
            ->get();
        $external_total_points = DB::table('assignments')
            ->whereIn('id', $assignment_ids)
            ->where('source', 'x')
            ->get();

        foreach ($adapt_total_points as $key => $value) {
            $total_points_by_assignment_id[$value->assignment_id] = $randomized_assignment_total_points[$value->assignment_id] ?? $value->sum;
        }
        foreach ($external_total_points as $key => $value) {
            $total_points_by_assignment_id[$value->id] = $value->external_source_points;
        }

        return $total_points_by_assignment_id;
    }

    public function getAssignmentIds($assignments)
    {
        return $assignments->map(function ($assignment) {
            return collect($assignment->toArray())
                ->all()['id'];
        })->toArray();
    }

    public function getNumberOfResetsByQuestionId(): array
    {
        $number_of_resets = DB::table('assignment_question_learning_tree')
            ->join('assignment_question', 'assignment_question_learning_tree.assignment_question_id', '=', 'assignment_question.id')
            ->select('question_id', 'number_of_resets')
            ->where('assignment_id', $this->id)
            ->get();
        $number_of_resets_by_question_id = [];
        foreach ($number_of_resets as $reset) {
            $number_of_resets_by_question_id[$reset->question_id] = $reset->number_of_resets;
        }
        return $number_of_resets_by_question_id;

    }

    /**
     * @return bool
     */
    function cannotAddOrRemoveQuestionsForQuestionWeightAssignment(): bool
    {
        return $this->points_per_question === 'question weight'
            && ($this->submissions->isNotEmpty() + $this->fileSubmissions->isNotEmpty());
    }

    /**
     * @param Assignment $new_assignment
     * @param $default_timing
     * @return mixed
     */
    function saveAssignmentTimingAndGroup(Assignment $new_assignment, $default_timing = null)
    {

        $assignToTiming = new AssignToTiming();
        $assignToTiming->assignment_id = $new_assignment->id;
        $assignToTiming->available_from = $default_timing
            ? $default_timing->available_from
            : Carbon::now()->startOfMinute()->toDateTimeString();
        $assignToTiming->due = $default_timing
            ? $default_timing->due
            : Carbon::now()->startOfMinute()->toDateTimeString();
        if ($new_assignment->late_policy !== 'not accepted') {
            $assignToTiming->final_submission_deadline = $default_timing
                ? $default_timing->final_submission_deadline
                : Carbon::now()->startOfMinute()->toDateTimeString();
        }
        $assignToTiming->save();
        $assignToGroup = new AssignToGroup();
        $assignToGroup->assign_to_timing_id = $assignToTiming->id;
        $assignToGroup->group = 'course';
        $assignToGroup->group_id = $new_assignment->course_id;
        $assignToGroup->save();
        return $assignToTiming->id;
    }

    /**
     * @param User $user
     * @return bool
     */
    public function ltiLaunchExists(User $user): bool
    {
        return DB::table('lti_launches')
            ->where('assignment_id', $this->id)
            ->where('user_id', $user->id)
            ->get()
            ->isNotEmpty();

    }

    /**
     * @return array
     */
    public function ltiLaunchesByUserId(): array
    {

        $lti_launches = DB::table('lti_launches')->where('assignment_id', $this->id)->get();

        $lti_launches_by_user_id = [];
        foreach ($lti_launches as $lti_launch) {
            $lti_launches_by_user_id[$lti_launch->user_id] = $lti_launch;
        }
        return $lti_launches_by_user_id;
    }

    public function assignToTimings()
    {
        return $this->hasMany(AssignToTiming::class);
    }

    /**
     * @param $user_id
     * @return false|mixed
     */
    public function assignToTimingDueDateGivenUserId($user_id)
    {
//see assignToTimingByUser below. Script needed if no user is logged in (like in Command)
        $assign_to_timing = DB::table('assignments')
            ->join('assign_to_timings', 'assignments.id', '=', 'assign_to_timings.assignment_id')
            ->join('assign_to_users', 'assign_to_timings.id', '=', 'assign_to_users.assign_to_timing_id')
            ->where('assignment_id', $this->id)
            ->where('user_id', $user_id)
            ->first();

        if (!$assign_to_timing) {
            return false;
        }
        $assign_to_timing_id = $assign_to_timing->assign_to_timing_id;
        return $this->assignToTimings->where('id', $assign_to_timing_id)->first()['due'];


    }

    public function assignToTimingByUser($key = '')
    {
        /** $assign_to_timing = $this->assignToUsers
         * ->where('user_id', auth()->user()->id)
         * ->first();
         **/
        $assign_to_timing = DB::table('assignments')
            ->join('assign_to_timings', 'assignments.id', '=', 'assign_to_timings.assignment_id')
            ->join('assign_to_users', 'assign_to_timings.id', '=', 'assign_to_users.assign_to_timing_id')
            ->where('assignment_id', $this->id)
            ->where('user_id', auth()->user()->id)
            ->first();

        if (!$assign_to_timing) {
            return false;
        }
        $assign_to_timing_id = $assign_to_timing->assign_to_timing_id;
        $assign_to_timing = $this->assignToTimings->where('id', $assign_to_timing_id)->first();

        return $key ? $assign_to_timing[$key] : $assign_to_timing;

    }

    /**
     * @param int $user_role
     * @return array
     */
    public function getEnrolledStudentIdsByAssignment(int $user_role): array
    {
        $enrollment = new Enrollment();
        $enrollments_info = $enrollment->getEnrolledUsersByRoleCourseSection($user_role, $this->course, 0);
        $student_ids = [];
        foreach ($enrollments_info as $info) {
            $student_ids[] = $info->id;
        }
        return $student_ids;
    }

    /**
     * @param $user
     * @return bool
     */
    public function overrideAccess($user)
    {
        $grader_access = false;
        foreach ($this->gradersAccess() as $value) {
            if ($value['user_id'] === $user->id) {
                $grader_access = true;
            }
        }
        return $grader_access || $this->course->user_id === $user->id;
    }


    public function gradersAccess()
    {
        $graders = $this->course->graderInfo();

        $assignment_grader_accesses = DB::table('assignment_grader_access')
            ->join('users', 'assignment_grader_access.user_id', '=', 'users.id')
            ->where('assignment_id', $this->id)
            ->get();
        $assignment_grader_access_by_id = [];
        foreach ($assignment_grader_accesses as $assignment_grader_access) {
            $assignment_grader_access_by_id[$assignment_grader_access->user_id] = $assignment_grader_access->access_level;
        }
        foreach ($graders as $key => $grader) {
            $graders[$key]['access_level'] = $assignment_grader_access_by_id[$grader['user_id']] ?? -1;
        }
        return $graders;
    }

    public function isBetaAssignment()
    {
        return DB::table('beta_assignments')->where('id', $this->id)->first() !== null;
    }

    public function assignToUsers()
    {
        return $this->hasManyThrough(AssignToUser::class, AssignToTiming::class);
    }

    public function cutUps()
    {
        return $this->hasMany(Cutup::class);
    }

    /**
     * @param Course $course
     * @param Extension $extension
     * @param Score $Score
     * @param Submission $Submission
     * @param Solution $Solution
     * @param AssignmentGroup $AssignmentGroup
     * @return array
     * @throws Exception
     */
    public function getAssignmentsByCourse(Course          $course,
                                           Extension       $extension,
                                           Score           $Score, Submission $Submission,
                                           Solution        $Solution,
                                           AssignmentGroup $AssignmentGroup)
    {

        $response['type'] = 'error';
        $assigned_assignment_ids = [];
        $assigned_assignments = [];
        try {
            if (Auth::user()->role === 3) {
                $solutions_by_assignment = $Solution->getSolutionsByAssignment($course);
                $assigned_assignments = $course->assignedToAssignmentsByUser();
                $assigned_assignment_ids = array_keys($assigned_assignments);
                $extensions_by_assignment = $extension->getUserExtensionsByAssignment(Auth::user());
                $total_points_by_assignment = $this->getTotalPointsForShownAssignments($course);
                [$scores_by_assignment, $z_scores_by_assignment] = $Score->getUserScoresByAssignment($course, Auth::user());
                $number_of_submissions_by_assignment = $Submission->getNumberOfUserSubmissionsByCourse($course, Auth::user());

            } else {
                $assign_to_groups = $this->assignToGroupsByCourse($course);
            }


            $course_assignments = $course->assignments;
            $course_beta_assignment_ids = $course->betaAssignmentIds();

            if (Auth::user()->role === 4) {
                $accessible_assignment_ids = $course->accessbileAssignmentsByGrader(Auth::user()->id);
            }
            $assignment_groups_by_assignment = $AssignmentGroup->assignmentGroupsByCourse($course->id);
            $assignments_info = [];
            $assignment_ids = $course_assignments->pluck('id')->toArray();
            $number_of_questions_assignments = request()->user()->role === 2
                ? $assignment_ids
                : $assigned_assignment_ids;
            $num_questions_results = DB::table('assignment_question')
                ->join('questions', 'assignment_question.question_id', '=', 'questions.id')
                ->whereIn('assignment_id', $number_of_questions_assignments)
                ->select('assignment_id', DB::raw('COUNT(*) AS num_questions'))
                ->groupBy('assignment_id')
                ->get();

            $associated_beta_assignments = DB::table('beta_assignments')
                ->whereIn('alpha_assignment_id', $assignment_ids)
                ->get();

            $beta_assignment_exists_ids = [];
            if ($associated_beta_assignments->isNotEmpty()) {
                foreach ($associated_beta_assignments as $associated_beta_assignment) {
                    if (!in_array($associated_beta_assignment->alpha_assignment_id, $beta_assignment_exists_ids)) {
                        $beta_assignment_exists_ids[] = $associated_beta_assignment->alpha_assignment_id;
                    }
                }
            }
            $topics_by_assignment_id = request()->user()->role === 2
                ? $this->getTopicsByAssignmentId($course, $assignment_ids)
                : [];

            $num_of_questions_by_assignment_id = [];
            foreach ($num_questions_results as $result) {
                $num_of_questions_by_assignment_id[$result->assignment_id] = $result->num_questions;
            }

            $assignment_question_where_not_owned = DB::table('assignment_question')
                ->join('questions', 'assignment_question.question_id', '=', 'questions.id')
                ->whereIn('assignment_question.assignment_id', $course->assignments()->pluck('id')->toArray())
                ->where('question_editor_user_id', '<>', Auth::user()->id)
                ->select('assignment_id', 'question_id')
                ->get();
            $does_not_own_all_questions = [];
            foreach ($assignment_question_where_not_owned as $value) {
                $does_not_own_all_questions[] = $value->assignment_id;
            }


            foreach ($course_assignments as $key => $assignment) {

                $num_questions = $num_of_questions_by_assignment_id[$assignment->id] ?? 0;
                if (Auth::user()->role === 3 && !in_array($assignment->id, $assigned_assignment_ids)) {
                    continue;
                }

                if (Auth::user()->role === 4 && !$accessible_assignment_ids[$assignment->id]) {
                    continue;
                }
                $assignments_info[$key] = $assignment->attributesToArray();
                $assignments_info[$key]['is_in_lms_course'] = $assignment->course->lms;
                $assignments_info[$key]['shown'] = $assignment->shown;
                $assignments_info[$key]['is_beta_assignment'] = in_array($assignment->id, $course_beta_assignment_ids);
                $assignments_info[$key]['owns_all_questions'] = !in_array($assignment->id, $does_not_own_all_questions);
                if (Auth::user()->role === 3) {
                    $is_extension = isset($extensions_by_assignment[$assignment->id]);
                    $available_from = $assigned_assignments[$assignment->id]->available_from;
                    $due = $is_extension ? $extensions_by_assignment[$assignment->id] : $assigned_assignments[$assignment->id]->due;
                    $assignments[$key]['is_extension'] = isset($extensions_by_assignment[$assignment->id]);

                    $assignments_info[$key]['due'] = [
                        'due_date' => $this->convertUTCMysqlFormattedDateToLocalDateAndTime($due, Auth::user()->time_zone), //for viewing
                        'is_extension' => $is_extension
                    ];//for viewing

                    //for comparing I just want the UTC version
                    $assignments_info[$key]['is_available'] = strtotime($available_from) < time();
                    $assignments_info[$key]['past_due'] = $due < time();
                    $assignments_info[$key]['score'] = is_numeric($scores_by_assignment[$assignment->id]) ? Helper::removeZerosAfterDecimal(round((float)$scores_by_assignment[$assignment->id], 2)) : $scores_by_assignment[$assignment->id];

                    $assignments_info[$key]['z_score'] = $z_scores_by_assignment[$assignment->id];
                    $assignments_info[$key]['number_submitted'] = $number_of_submissions_by_assignment[$assignment->id];
                    $assignments_info[$key]['solution_key'] = $solutions_by_assignment[$assignment->id];
                    $assignments_info[$key]['total_points'] = isset($total_points_by_assignment[$assignment->id]) ? Helper::removeZerosAfterDecimal(round($total_points_by_assignment[$assignment->id], 2)) : 0;
                    $assignments_info[$key]['num_questions'] = $assignment->number_of_randomized_assessments
                        ?: $num_questions;

                    $assignments_info[$key]['available_from'] = $this->convertUTCMysqlFormattedDateToLocalDateAndTime($available_from, Auth::user()->time_zone);
                } else {
                    $assignments_info[$key]['tethered_beta_assignment_exists'] = in_array($assignment->id, $beta_assignment_exists_ids);
                    $assignments_info[$key]['default_points_per_question'] = Helper::removeZerosAfterDecimal($assignment->default_points_per_question);
                    $assignments_info[$key]['total_points'] = Helper::removeZerosAfterDecimal(round($assignment->total_points, 2));
                    $assignments_info[$key]['num_questions'] = $num_questions;//to be consistent with other collections
                    $assignments_info[$key]['assign_tos'] = array_values($assign_to_groups[$assignment->id]);
                    $num_assign_tos = 0;
                    $num_open = 0;
                    $num_closed = 0;
                    $num_upcoming = 0;
                    foreach ($assignments_info[$key]['assign_tos'] as $assign_to_key => $assign_to) {
                        $available_from = $assign_to['available_from'];
                        $due = $assign_to['due'];
                        $final_submission_deadline = $assign_to['final_submission_deadline'];
                        $status = $this->getStatus($available_from, $due);
                        switch ($status) {
                            case('Open'):
                                $num_open++;
                                break;
                            case('Closed'):
                                $num_closed++;
                                break;
                            case('Upcoming'):
                                $num_upcoming++;
                        }
                        $num_assign_tos++;
                        $assignments_info[$key]['assign_tos'][$assign_to_key]['status'] = $status;
                        $assignments_info[$key]['assign_tos'][$assign_to_key]['available_from'] = $this->convertUTCMysqlFormattedDateToLocalDateAndTime($available_from, Auth::user()->time_zone);
                        $assignments_info[$key]['assign_tos'][$assign_to_key]['available_from_date'] = $this->convertUTCMysqlFormattedDateToLocalDate($available_from, Auth::user()->time_zone);
                        $assignments_info[$key]['assign_tos'][$assign_to_key]['available_from_time'] = $this->convertUTCMysqlFormattedDateToLocalTime($available_from, Auth::user()->time_zone);


                        /*  if ($assignment->late_policy !== 'not accepted' && !$final_submission_deadline){
                              Log::info($assignment->id);
                              $final_submission_deadline = $due;
                          }*/
                        //not sure why but in a beta course, this somehow didn't come through when copying; the code above will at least show the issue
                        $assignments_info[$key]['assign_tos'][$assign_to_key]['final_submission_deadline_date'] = ($assignment->late_policy !== 'not accepted')
                            ? $this->convertUTCMysqlFormattedDateToLocalDate($final_submission_deadline, Auth::user()->time_zone)
                            : null;
                        $assignments_info[$key]['assign_tos'][$assign_to_key]['final_submission_deadline_time'] = ($assignment->late_policy !== 'not accepted')
                            ? $this->convertUTCMysqlFormattedDateToLocalTime($final_submission_deadline, Auth::user()->time_zone)
                            : null;
                        $assignments_info[$key]['assign_tos'][$assign_to_key]['due'] = $this->convertUTCMysqlFormattedDateToLocalDateAndTime($due, Auth::user()->time_zone);
                        $assignments_info[$key]['assign_tos'][$assign_to_key]['due_date'] = $this->convertUTCMysqlFormattedDateToLocalDate($due, Auth::user()->time_zone);
                        $assignments_info[$key]['assign_tos'][$assign_to_key]['due_time'] = $this->convertUTCMysqlFormattedDateToLocalTime($due, Auth::user()->time_zone);
                        $assignments_info[$key]['topics'] = Auth::user()->role === 2 ? $topics_by_assignment_id[$assignment->id] : [];

                    }
                    $assignments_info[$key]['overall_status'] = $this->getOverallStatus($num_assign_tos, $num_open, $num_closed, $num_upcoming);
                    $assignments_info[$key]['has_submissions_or_file_submissions'] = $this->hasSubmissionsOrFileSubmissions($assignment->id);


                }
//same regardless of whether you're a student
                $assignments_info[$key]['assignment_group'] = $assignment_groups_by_assignment[$assignment->id];
                $assignments_info[$key]['show_points_per_question'] = $assignment->show_points_per_question;
                $assignments_info[$key]['assessment_type'] = $assignment->assessment_type;


                if (Auth::user()->role === 3 && !$assignments_info[$key]['shown']) {
                    unset($assignments_info[$key]);
                }

            }
            $response['assignments'] = array_values($assignments_info);//fix the unset
            $response['type'] = 'success';
        } catch
        (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error retrieving your assignments.  Please try again by refreshing the page or contact us for assistance.";

        }
        return $response;
    }

    /**
     * @param int $assignment_id
     * @return bool
     */
    function hasSubmissionsOrFileSubmissions(int $assignment_id): bool
    {
        //don't include fake students
        if (DB::table('submissions')
            ->join('users', 'submissions.user_id', 'users.id')
            ->where('assignment_id', $assignment_id)
            ->where('fake_student', 0)
            ->first()) {
            return true;
        }
        if (DB::table('submission_files')
            ->join('users', 'submission_files.user_id', 'users.id')
            ->where('assignment_id', $assignment_id)
            ->where('fake_student', 0)
            ->first()) {
            return true;
        }
        return false;

    }

    function assignToGroups()
    {
        $assign_to_groups_by_course = $this->assignToGroupsByCourse($this->course);
        foreach ($assign_to_groups_by_course as $assignment_id => $assign_to_group) {
            if ($assignment_id === $this->id)
                return array_values($assign_to_group);
        }
    }

    /**
     * @param Course $course
     * @return array
     */
    function assignToGroupsByCourse(Course $course)
    {
        $assignment_ids = $course->assignments->pluck('id')->toArray();

        $assign_to_groups_info = DB::table('assignments')
            ->whereIn('assignment_id', $assignment_ids)
            ->join('assign_to_timings', 'assignments.id', '=', 'assign_to_timings.assignment_id')
            ->join('assign_to_groups', 'assign_to_timings.id', '=', 'assign_to_groups.assign_to_timing_id')
            ->select('assignments.id AS assignment_id',
                'assign_to_timings.id AS assign_to_timing_id',
                'assign_to_groups.group',
                'assign_to_groups.group_id',
                'assign_to_timings.available_from',
                'assign_to_timings.due',
                'assign_to_timings.final_submission_deadline')
            ->where('assignments.course_id', $course->id)
            ->get();

        $section_ids = [];
        $user_ids = [];
        foreach ($assign_to_groups_info as $assign_to_group) {
            if ($assign_to_group->group === 'section') {
                $section_ids[] = $assign_to_group->group_id;
            }
            if ($assign_to_group->group === 'user') {
                $user_ids[] = $assign_to_group->group_id;
            }
        }

        $sections_info = DB::table('sections')
            ->whereIn('id', $section_ids)
            ->select('id', 'name')
            ->get();
        $users_info = DB::table('users')
            ->whereIn('id', $user_ids)
            ->select('id', DB::raw('CONCAT(first_name, " ", last_name) AS name'))
            ->get();
        $sections_by_id = [];
        $users_by_id = [];
        foreach ($sections_info as $section) {
            $sections_by_id[$section->id] = $section->name;
        }
        foreach ($users_info as $user) {
            $users_by_id[$user->id] = $user->name;
        }


        $assign_to_groups_by_assignment_id = [];
        foreach ($assign_to_groups_info as $assign_to_group) {
            $assignment_id = $assign_to_group->assignment_id;
            $assign_to_timing_id = $assign_to_group->assign_to_timing_id;

            if (!isset($assign_to_groups_by_assignment_id[$assignment_id])) {
                $assign_to_groups_by_assignment_id[$assignment_id] = [];
            }
            if (!isset($assign_to_groups_by_assignment_id[$assignment_id][$assign_to_timing_id])) {
                $assign_to_groups_by_assignment_id[$assignment_id][$assign_to_timing_id] = [
                    'available_from' => $assign_to_group->available_from,
                    'due' => $assign_to_group->due,
                    'final_submission_deadline' => $assign_to_group->final_submission_deadline];
                $assign_to_groups_by_assignment_id[$assignment_id][$assign_to_timing_id]['groups'] = [];
            }


            $group = 'Everybody';
            $formatted_group = ["value" => ["course_id" => $course->id], "text" => 'Everybody'];
            if ($assign_to_group->group === 'section') {
                $group = $sections_by_id[$assign_to_group->group_id];
                $formatted_group = ["value" => ["section_id" => $assign_to_group->group_id], "text" => $group];
            } else if ($assign_to_group->group === 'user') {
                $group = $users_by_id[$assign_to_group->group_id];
                $formatted_group = ["value" => ["user_id" => $assign_to_group->group_id], "text" => $group];
            }
            $assign_to_groups_by_assignment_id[$assignment_id][$assign_to_timing_id]['groups'][] = $group;
            $assign_to_groups_by_assignment_id[$assignment_id][$assign_to_timing_id]['formatted_groups'][] = $formatted_group;
        }

        return $assign_to_groups_by_assignment_id;

    }

    function assignToGroupsByCourseAndUser(Course $course)
    {
        $assignment_ids = $course->assignments->pluck('id')->toArray();
        $assign_tos = DB::table('assign_tos')->whereIn('assignment_id', $assignment_ids)
            ->get();
        $assign_tos_by_assignment_id_and_user = [];

        foreach ($assign_tos as $assign_to) {
            $assignment_id = $assign_to->assignment_id;
            $assign_to_data = ['available_from' => $assign_to->available_from,
                'due' => $assign_to->due];
            if ($assign_to->course_id) {
                $enrollments = $course->enrollments()->pluck('user_id')->toArray();
                foreach ($enrollments as $enrollment) {
                    $assign_tos_by_assignment_id_and_user[$assignment_id][$enrollment] = $assign_to_data;
                }

            } else if ($assign_to->section_id) {

                echo "Do section ids";
            } else if ($assign_to->user_id) {

                echo "Do user ids";
            } else {
                echo "need to do this.";
            }

        }

        return $assign_tos_by_assignment_id_and_user;

    }

    /**
     * @param Course $course
     * @return array
     */
    function getTotalPointsForShownAssignments(Course $course)
    {
        $total_points_by_assignment = [];
        $randomized_point_info = $points_info = DB::table('assignments')
            ->where('assignments.course_id', $course->id)
            ->where('assignments.shown', 1)
            ->whereNotNull('assignments.number_of_randomized_assessments')
            ->select(DB::raw('number_of_randomized_assessments * default_points_per_question AS total_points'), 'id')
            ->get();

        foreach ($randomized_point_info as $value) {
            $total_points_by_assignment [$value->id] = $value->total_points;
        }

        $points_info = DB::table('assignment_question')
            ->join('assignments', 'assignment_question.assignment_id', '=', 'assignments.id')
            ->where('assignments.course_id', $course->id)
            ->where('assignments.shown', 1)
            ->whereNull('assignments.number_of_randomized_assessments')
            ->groupBy('assignments.id')
            ->select(DB::raw('SUM(assignment_question.points) as total_points,assignments.id'))
            ->get();
        foreach ($points_info as $value) {
            $total_points_by_assignment [$value->id] = Helper::removeZerosAfterDecimal($value->total_points);
        }
        $points_info = DB::table('assignments')
            ->where('course_id', $course->id)
            ->where('source', 'x')
            ->where('shown', 1)
            ->select('id', 'external_source_points')
            ->get();
        foreach ($points_info as $value) {
            $total_points_by_assignment [$value->id] = $value->external_source_points;
        }


        return $total_points_by_assignment;


    }

    public function getStatus(string $available_from, string $due)
    {
        if (Carbon::now() < Carbon::parse($available_from)) {
            return 'Upcoming';
        }

        if (Carbon::now() < Carbon::parse($due)) {
            return 'Open';
        }
        return 'Closed';
    }

    public function graders()
    {
        return $this->belongsToMany('App\User', 'assignment_grader_access')
            ->withPivot('access_level')
            ->withTimestamps();

    }

    public function getEditingFormItems(string $available_from, string $due, $final_submission_deadline, Assignment $assignment)
    {
        $editing_form_items = [];
        $editing_form_items['status'] = $this->getStatus($available_from, $due);
        $editing_form_items['available_from_date'] = $this->convertUTCMysqlFormattedDateToLocalDate($available_from, Auth::user()->time_zone);
        $editing_form_items['available_from_time'] = $this->convertUTCMysqlFormattedDateToLocalTime($available_from, Auth::user()->time_zone);
        $editing_form_items['final_submission_deadline_date'] = $final_submission_deadline ? $this->convertUTCMysqlFormattedDateToLocalDate($final_submission_deadline, Auth::user()->time_zone) : null;
        $editing_form_items['final_submission_deadline_time'] = $final_submission_deadline ? $this->convertUTCMysqlFormattedDateToLocalTime($final_submission_deadline, Auth::user()->time_zone) : null;
        $editing_form_items['due_date'] = $this->convertUTCMysqlFormattedDateToLocalDate($due, Auth::user()->time_zone);
        $editing_form_items['due_time'] = $this->convertUTCMysqlFormattedDateToLocalTime($due, Auth::user()->time_zone);
        $editing_form_items['has_submissions_or_file_submissions'] = $assignment->hasNonFakeStudentFileOrQuestionSubmissions();
        $editing_form_items['include_in_weighted_average'] = $assignment->include_in_weighted_average;
        if ($assignment->default_open_ended_submission_type === 'text') {
            $editing_form_items['default_open_ended_submission_type'] = "{$assignment->default_open_ended_text_editor} text";
        }
        return $editing_form_items;
    }

    public function getNewAssignmentOrder(Course $course)
    {
        $max_order = DB::table('assignments')
            ->where('course_id', $course->id)
            ->max('order');
        return $max_order ? $max_order + 1 : 1;
    }

    public function orderAssignments(array $ordered_assignments, Course $course)
    {
        foreach ($ordered_assignments as $key => $assignment_id) {
            DB::table('assignments')->where('course_id', $course->id)//validation step!
            ->where('id', $assignment_id)
                ->update(['order' => $key + 1]);
        }
    }

    public function questions()
    {
        return $this->belongsToMany('App\Question', 'assignment_question')
            ->withPivot('order')
            ->orderBy('assignment_question.order')
            ->withTimestamps();
    }


    public function scores()
    {
        return $this->hasMany('App\Score');
    }

    public function canGiveUps()
    {
        return $this->hasMany('App\CanGiveUp');
    }

    public function seeds()
    {
        return $this->hasMany('App\Seed');
    }


    public function betaAssignments()
    {
        if (!$this->course->alpha) {
            return [];
        }
        $beta_assignment_ids = DB::table('beta_assignments')
            ->where('alpha_assignment_id', $this->id)
            ->get();

        if ($beta_assignment_ids->isNotEmpty()) {
            $beta_assignment_ids = $beta_assignment_ids->pluck('id')->toArray();
        }
        return $this->whereIn('assignments.id', $beta_assignment_ids)
            ->get();
    }

    /**
     * @return array
     */
    public function betaAssignmentIds()
    {
        if (!$this->course->alpha) {
            return [];
        }
        $beta_assignments = DB::table('beta_assignments')
            ->where('alpha_assignment_id', $this->id)
            ->get();

        return $beta_assignments->isNotEmpty()
            ? $beta_assignments->pluck('id')->toArray()
            : [];

    }

    /**
     * @return Assignment[]
     */
    public function addBetaAssignments()
    {
        $assignments = [$this];
        $beta_assignments = $this->betaAssignments();
        foreach ($beta_assignments as $beta_assignment) {
            $assignments[] = $beta_assignment;
        }
        return $assignments;
    }

    /**
     * @return Assignment[]
     */
    public function addBetaAssignmentIds(): array
    {
        $assignment_ids = [$this->id];
        $beta_assignment_ids = $this->betaAssignmentIds();
        foreach ($beta_assignment_ids as $beta_assignment_id) {
            $assignment_ids[] = $beta_assignment_id;
        }
        return $assignment_ids;
    }

    public function course()
    {
        return $this->belongsTo('App\Course');
    }

    public function fileSubmissions()
    {

        return $this->hasMany('App\SubmissionFile');
    }

    public function assignmentFileSubmissions()
    {
        return $this->hasMany('App\SubmissionFile')->where('type', 'a');
    }

    public function hasFileOrQuestionSubmissions()
    {
        return $this->submissions->isNotEmpty() + $this->fileSubmissions->isNotEmpty();
    }

    /**
     * @param array $assignment_ids
     * @return bool
     */
    public function hasNonFakeStudentFileOrQuestionSubmissions($assignment_ids = [])
    {
        if (!$assignment_ids) {
            $assignment_ids = [$this->id];
        }
        $submission_files_not_empty = DB::table('submission_files')
            ->join('users', 'submission_files.user_id', '=', 'users.id')
            ->whereIn('assignment_id', $assignment_ids)
            ->where('fake_student', 0)
            ->get()
            ->isNotEmpty();

        $submissions_not_empty = DB::table('submissions')
            ->join('users', 'submissions.user_id', '=', 'users.id')
            ->whereIn('assignment_id', $assignment_ids)
            ->where('fake_student', 0)
            ->get()
            ->isNotEmpty();
        return $submission_files_not_empty || $submissions_not_empty;


    }

    public function questionFileSubmissions()
    {
        $questionFileSubmissions = DB::table('submission_files')
            ->leftJoin('users', 'grader_id', '=', 'users.id')
            ->whereIn('type', ['q', 'text', 'audio'])
            ->where('assignment_id', $this->id)
            ->select('submission_files.*', DB::raw('CONCAT(users.first_name," ", users.last_name) AS grader_name'))
            ->get();

        return collect($questionFileSubmissions);
    }

    public function learningTrees()
    {
        $learningTrees = DB::table('assignment_question')
            ->join('assignment_question_learning_tree', 'assignment_question.id', '=', 'assignment_question_learning_tree.assignment_question_id')
            ->join('learning_trees', 'assignment_question_learning_tree.learning_tree_id', '=', 'learning_trees.id')
            ->where('assignment_id', $this->id)
            ->select('learning_tree', 'question_id', 'learning_tree_id')
            ->get();
        return collect($learningTrees);
    }

    public function idByCourseAssignmentUser($assignment_course_as_string)
    {
        $assignment_course_info = explode(' --- ', $assignment_course_as_string);
        if (!isset($assignment_course_info[1])) {
            return false;
        }
        $assignment = DB::table('assignments')
            ->join('courses', 'assignments.course_id', '=', 'courses.id')
            ->where('courses.name', $assignment_course_info[0])
            ->where('assignments.name', $assignment_course_info[1])
            ->where('courses.user_id', request()->user()->id)
            ->select('assignments.id')
            ->first();
        return $assignment ? $assignment->id : false;

    }

    public function submissions()
    {
        return $this->hasMany('App\Submission');
    }

    public function extensions()
    {
        return $this->hasMany('App\Extension');
    }

    public function removeUserInfo(User             $user,
                                                    $assignments_to_remove_ids,
                                                    $assign_to_timings_to_remove_ids,
                                   Submission       $submission,
                                   SubmissionFile   $submissionFile,
                                   Score            $score,
                                   AssignToUser     $assignToUser,
                                   Extension        $extension,
                                   LtiGradePassback $ltiGradePassback,
                                   Seed             $seed)
    {
        $submission->where('user_id', $user->id)->whereIn('assignment_id', $assignments_to_remove_ids)->delete();
        $submissionFile->where('user_id', $user->id)->whereIn('assignment_id', $assignments_to_remove_ids)->delete();
        $score->where('user_id', $user->id)->whereIn('assignment_id', $assignments_to_remove_ids)->delete();
        $assignToUser->where('user_id', $user->id)->whereIn('assign_to_timing_id', $assign_to_timings_to_remove_ids)->delete();
        $extension->where('user_id', $user->id)->whereIn('assignment_id', $assignments_to_remove_ids)->delete();
        $ltiGradePassback->where('user_id', $user->id)->whereIn('assignment_id', $assignments_to_remove_ids)->delete();
        $seed->where('user_id', $user->id)->whereIn('assignment_id', $assignments_to_remove_ids)->delete();
        DB::table('review_histories')->where('user_id', $user->id)->whereIn('assignment_id', $assignments_to_remove_ids)->delete();
        DB::table('assignment_question_time_on_tasks')->where('user_id', $user->id)->whereIn('assignment_id', $assignments_to_remove_ids)->delete();
        DB::table('randomized_assignment_questions')->where('user_id', $user->id)->whereIn('assignment_id', $assignments_to_remove_ids)->delete();
    }

    public function assignToTimingsByUser()
    {
        $assign_to_timings_by_user = [];
        $assign_to_timings = DB::table('assign_to_timings')
            ->join('assign_to_users', 'assign_to_timings.id', '=', 'assign_to_users.assign_to_timing_id')
            ->where('assign_to_timings.assignment_id', $this->id)
            ->get();
        foreach ($assign_to_timings as $key => $assign_to_timing) {
            $assign_to_timings_by_user[$assign_to_timing->user_id] = $assign_to_timing;
        }
        return $assign_to_timings_by_user;
    }

    public function getOverallStatus(int $num_assign_tos, int $num_open, int $num_closed, int $num_upcoming)
    {
        if ($num_assign_tos === $num_open) {
            return 'Open';
        }

        if ($num_assign_tos === $num_closed) {
            return 'Closed';
        }
        if ($num_assign_tos === $num_upcoming) {
            return 'Upcoming';
        }
        return 'Partial';
    }

    /**
     * @return array
     */
    public function questionInAssignmentInformation(): array
    {
        $assignment_ids = $this->course->assignments->pluck('id')->toArray();
        $assignment_questions = DB::table('assignment_question')
            ->join('assignments', 'assignment_question.assignment_id', '=', 'assignments.id')
            ->whereIn('assignment_id', $assignment_ids)
            ->select('question_id', 'assignments.name')
            ->get();
        $in_assignments = [];
        foreach ($assignment_questions as $assignment_question) {
            if (!isset($in_assignments[$assignment_question->question_id])) {
                $in_assignments[$assignment_question->question_id] = [];
            }
            $in_assignments[$assignment_question->question_id][] = $assignment_question->name;
        }
        return $in_assignments;

    }

    public function scaleColumnsWithNewTotalPoints($new_total_points)
    {
        $tables_columns = [
            ['table' => 'assignment_question', 'column' => 'points'],
            ['table' => 'submissions', 'column' => 'score'],
            ['table' => 'submission_files', 'column' => 'score'],
            ['table' => 'scores', 'column' => 'score']
        ];
        foreach ($tables_columns as $value) {
            $table = $value['table'];
            $column = $value['column'];
            $rows = DB::table($table)->where('assignment_id', $this->id)->get();

            foreach ($rows as $row) {
                DB::table($table)->where('id', $row->id)
                    ->update([$column => $row->{$column} * ($new_total_points / $this->total_points)]);

            }
        }

    }

    /**
     * @param Course $course
     * @param array $assignment_ids
     * @return array
     */
    public function getTopicsByAssignmentId(Course $course, array $assignment_ids): array
    {
        $topics_by_assignment_id = [];
        $num_questions_by_topic_id = [];
        foreach ($course->assignments as $assignment) {
            $topics_by_assignment_id[$assignment->id] = [];
        }
        $assignment_topics = DB::table('assignment_topics')
            ->whereIn('assignment_id', $assignment_ids)
            ->select('assignment_id', 'name', 'id')
            ->orderBy('name')
            ->get();
        if ($assignment_topics) {
            $assignment_topic_num_questions_results = DB::table('assignment_question')
                ->whereIn('assignment_topic_id', $assignment_topics->pluck('id')->toArray())
                ->whereIn('assignment_id', $assignment_ids)
                ->select('assignment_topic_id', DB::raw('COUNT(*) AS num_questions'))
                ->groupBy('assignment_topic_id')
                ->get();
            foreach ($assignment_topic_num_questions_results as $assignment_topic_num_questions_result) {
                $num_questions_by_topic_id[$assignment_topic_num_questions_result->assignment_topic_id] = $assignment_topic_num_questions_result->num_questions;
            }
        }
        foreach ($assignment_topics as $assignment_topic) {
            $assignment_topic->num_questions = $num_questions_by_topic_id[$assignment_topic->id] ?? 0;
            $topics_by_assignment_id[$assignment_topic->assignment_id][] = $assignment_topic;
        }
        return $topics_by_assignment_id;
    }

}
