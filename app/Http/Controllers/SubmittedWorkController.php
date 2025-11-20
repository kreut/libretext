<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\Exceptions\Handler;
use App\Grader;
use App\Helpers\Helper;
use App\Question;
use App\Score;
use App\Submission;
use App\SubmittedWork;
use App\SubmittedWorkPendingScore;
use App\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class SubmittedWorkController extends Controller
{

    /**
     * @param Request $request
     * @param Assignment $assignment
     * @param Question $question
     * @param User $student_user
     * @param SubmittedWork $submittedWork
     * @param SubmittedWorkPendingScore $submittedWorkPendingScore
     * @param Grader $grader
     * @return array
     * @throws Exception
     */
    public function getSubmittedWorkWithPendingScore(Request                   $request,
                                                     Assignment                $assignment,
                                                     Question                  $question,
                                                     User                      $student_user,
                                                     SubmittedWork             $submittedWork,
                                                     SubmittedWorkPendingScore $submittedWorkPendingScore,
                                                     Grader                    $grader): array
    {
        try {
            $response['type'] = 'error';
            $authorized = Gate::inspect('getSubmittedWorkWithPendingScore',
                [$submittedWork, $assignment, $student_user, $grader]);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            $submitted_work_pending_score = $submittedWorkPendingScore->where('user_id', $student_user->id)
                ->where('assignment_id', $assignment->id)
                ->where('question_id', $question->id)
                ->first();
            $submitted_work = $submittedWork->where('user_id', $student_user->id)
                ->where('assignment_id', $assignment->id)
                ->where('question_id', $question->id)
                ->first();
            $pending_score = $submitted_work_pending_score ? $submitted_work_pending_score->score : null;
            $response['can_submit_work'] = $assignment->can_submit_work;
            $response['submitted_work'] = $submitted_work
                ? ['url' => Storage::disk('s3')->temporaryUrl("submitted-work/{$assignment->id}/{$submitted_work->submitted_work}", now()->addDay()),
                    'format' => $submitted_work->format,
                    'pending_score' => $pending_score,
                    'submitted_at' => $submitted_work->updated_at
                        ->tz($request->user()->time_zone)
                        ->format('M d, Y \a\t g:i:s a')]
                : ['pending_score' => $pending_score];
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to get the submitted work information for this student.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    /**
     * @param Request $request
     * @param Assignment $assignment
     * @param SubmittedWork $submittedWork
     * @return array
     * @throws Exception
     */
    public
    function storeAudioSubmittedWork(Request       $request,
                                     Assignment    $assignment,
                                     SubmittedWork $submittedWork): array
    {

        try {
            $response['type'] = 'error';
            $authorized = Gate::inspect('storeAudioSubmittedWork', [$submittedWork, $assignment]);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            $file = Helper::storeAudio($request, "submitted-work/$assignment->id");
            $response['type'] = 'success';
            $response['file'] = $file;
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to save your audio submitted work.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    /**
     * @param Request $request
     * @param Assignment $assignment
     * @param Question $question
     * @param SubmittedWork $submittedWork
     * @return array
     * @throws Exception
     */
    public
    function show(Request       $request,
                  Assignment    $assignment,
                  Question      $question,
                  SubmittedWork $submittedWork): array
    {

        try {
            $response['type'] = 'error';
            $submitted_work = $submittedWork->where('assignment_id', $assignment->id)
                ->where('question_id', $question->id)
                ->where('user_id', $request->user()->id)
                ->first();
            if ($submitted_work) {
                $submitted_work['submitted_work'] = Storage::disk('s3')->temporaryUrl("submitted-work/{$assignment->id}/{$submitted_work['submitted_work']}", now()->addDay());
                $submitted_work['submitted_work_at'] = $submitted_work['updated_at']
                    ->setTimezone($request->user()->time_zone) // Adjust to the user's timezone
                    ->format('M d, Y \a\t g:i:s a');
            }
            $response['submitted_work'] = $submitted_work;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to retrieve the submitted work.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    /**
     * @param Request $request
     * @param Assignment $assignment
     * @param Question $question
     * @param SubmittedWork $submittedWork
     * @param Submission $submission
     * @param SubmittedWorkPendingScore $submittedWorkPendingScore
     * @return array
     */
    public
    function store(Request                   $request,
                   Assignment                $assignment,
                   Question                  $question,
                   SubmittedWork             $submittedWork,
                   Submission                $submission,
                   SubmittedWorkPendingScore $submittedWorkPendingScore,
                   Score                     $score): array
    {

        try {
            $response['type'] = 'error';
            $submitted_work = $request->submitted_work;
            $authorized = Gate::inspect('store', [$submittedWork, $assignment]);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            $submitted_work_exists = Storage::disk('s3')->exists("$submitted_work");
            DB::beginTransaction();
            if ($submitted_work_exists) {
                $submittedWork->updateOrCreate(
                    [
                        'user_id' => $request->user()->id,
                        'assignment_id' => $assignment->id,
                        'question_id' => $question->id,
                    ],
                    [
                        'submitted_work' => pathinfo($submitted_work, PATHINFO_BASENAME),
                        'format' => $request->submitted_work_format
                    ]
                );

                $submission = $submission->where('user_id', $request->user()->id)
                    ->where('assignment_id', $assignment->id)
                    ->where('question_id', $question->id)
                    ->first();
                $move_score_from_pending = false;
                if ($submission && $assignment->submitted_work_policy === 'required with auto-approval') {
                    $submitted_work_pending_score = $submittedWorkPendingScore
                        ->where('user_id', $request->user()->id)
                        ->where('assignment_id', $assignment->id)
                        ->where('question_id', $question->id)
                        ->first();
                    if ($submitted_work_pending_score) {
                        $submission->score = $submitted_work_pending_score->score;
                        $submission->save();
                        $score->updateAssignmentScore($request->user()->id, $assignment->id, $assignment->lms_grade_passback === 'automatic');
                        $submitted_work_pending_score->delete();
                        $move_score_from_pending = true;
                    }

                }

                DB::commit();
                $response['message'] = "Your work has been submitted.";
                if ($move_score_from_pending) {
                    $response['message'] .= " In addition, your auto-graded submission has now been fully accepted.";
                }
                $response['type'] = 'success';
                $response['submitted_work_url'] = Storage::disk('s3')->temporaryUrl($submitted_work, now()->addDay());
                $response['submitted_work_at'] = Carbon::now('UTC') // Get the current UTC time
                ->setTimezone($request->user()->time_zone) // Adjust to the user's timezone
                ->format('M d, Y \a\t g:i:s a');

            } else {
                $response['message'] = "We were unable to locate the submitted work on the server.  Please try again or contact us for assistance.";
            }
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to save your submitted work.  Please try again or contact us for assistance.";
        }

        return $response;

    }

    /**
     * @param Request $request
     * @param Assignment $assignment
     * @param Question $question
     * @param SubmittedWork $submittedWork
     * @return array
     * @throws Exception
     */
    public
    function destroy(Request       $request,
                     Assignment    $assignment,
                     Question      $question,
                     SubmittedWork $submittedWork): array
    {

        try {
            $response['type'] = 'error';
            $authorized = Gate::inspect('delete', [$submittedWork, $assignment, $assignment->id, $question->id]);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            $submitted_work = $submittedWork->submitted_work;
            $submittedWork->where('user_id', $request->user()->id)
                ->where('assignment_id', $assignment->id)
                ->where('question_id', $question->id)
                ->delete();
            if (Storage::disk('s3')->exists("submitted-work/$assignment->id/$submitted_work")) {
                Storage::disk('s3')->delete("submitted-work/$assignment->id/$submitted_work");
            }
            $response['message'] = "Your submitted work has been deleted.";
            $response['type'] = 'info';

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to delete your submitted work.  Please try again or contact us for assistance.";
        }
        return $response;

    }
}
