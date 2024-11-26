<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\AssignmentSyncQuestion;
use App\Exceptions\Handler;
use App\Question;
use App\Submission;
use App\SubmissionHistory;
use App\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use PhpParser\Node\Expr\Cast\Object_;

class SubmissionHistoryController extends Controller
{

    /**
     * @param Request $request
     * @param Assignment $assignment
     * @param Question $question
     * @param Submission $Submission
     * @param AssignmentSyncQuestion $assignmentSyncQuestion
     * @return array
     * @throws Exception
     */
    public function getByAssignmentAndQuestion(Request                $request,
                                               Assignment             $assignment,
                                               Question               $question,
                                               Submission             $Submission,
                                               AssignmentSyncQuestion $assignmentSyncQuestion): array
    {

        try {
            $response['type'] = 'error';
            $user = User::find($request->user_id);
            $authorized = Gate::inspect('submissionArray', [$Submission, $assignment]);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            $submission_history = DB::table('submission_histories')
                ->where('assignment_id', $assignment->id)
                ->where('question_id', $question->id)
                ->where('user_id', $user->id)
                ->orderBy('id', 'DESC')
                ->get();
            $current_submission = DB::table('submissions')
                ->where('assignment_id', $assignment->id)
                ->where('question_id', $question->id)
                ->where('user_id', $user->id)
                ->first();
            foreach ($submission_history as $key => $value) {
                $carbon = new Carbon();
                $created_at = $value->created_at;
                $submission = new \stdClass();
                $submission->submission = $value->submission;
                $submission->submission_count = $current_submission->submission_count;
                $submission->show_solution = $current_submission->show_solution;


                $value->created_at = $carbon->parse($created_at, 'UTC')
                    ->setTimezone($user->time_zone)
                    ->format('M j, Y g:i:s a');
                if ($key === 0 && $request->user()->role === 3) {
                    $gave_up = DB::table('can_give_ups')
                        ->where('question_id', $question->id)
                        ->where('assignment_id', $assignment->id)
                        ->where('user_id', request()->user()->id)
                        ->where('status', 'gave up')
                        ->exists();
                    $real_time_show_solution = $assignmentSyncQuestion->showRealTimeSolution($assignment, $Submission, $submission, $question);
                    Cache::put('show_solution_' . request()->user()->id . '_' . $assignment->id . '_' . $question->id, $real_time_show_solution || $gave_up || $assignment->solutions_released, 10);
                }

                $value->submission_array = $Submission->getSubmissionArray($assignment, $question, $submission, false);

            }
            $response['submission_history'] = $submission_history;
            $response['type'] = 'success';


        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
        }
        return $response;


    }

    public function store(Request           $request,
                          Assignment        $assignment,
                          Question          $question,
                          SubmissionHistory $submissionHistory)
    {
        try {
            //TODO: Authorized by being enrolled in the class, can submit

            $submissionHistory->user_id = $request->user();
            $submissionHistory->assignment_id = $assignment->id;
            $submissionHistory->question_id = $question->id;
            $submissionHistory->save();
            session()->put('submission_history', $submissionHistory->id);

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);

        }
    }

}
