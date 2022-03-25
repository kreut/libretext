<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\Exceptions\Handler;
use App\LearningTree;
use App\Question;
use App\RemediationSubmission;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class RemediationSubmissionController extends Controller
{
    /**
     * @param Request $request
     * @param Assignment $assignment
     * @param LearningTree $learningTree
     * @param int $branch_id
     * @param Question $rootNodeQuestion
     * @param Question $remediation
     * @param RemediationSubmission $RemediationSubmission
     * @return array
     * @throws Exception
     */
    public function getTimeLeft(Request               $request,
                                Assignment            $assignment,
                                LearningTree          $learningTree,
                                int                   $branch_id,
                                Question              $rootNodeQuestion,
                                RemediationSubmission $RemediationSubmission): array
    {

        $response['type'] = 'error';
        /*$authorized = Gate::inspect('denyRefreshQuestion', $refreshQuestionRequest);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }*/
        try {
            $assignment_question_learning_tree = DB::table('assignment_question_learning_tree')
                ->join('assignment_question', 'assignment_question_learning_tree.assignment_question_id', '=', 'assignment_question.id')
                ->select('min_time','learning_tree_success_level')
                ->where('assignment_question.assignment_id', $assignment->id)
                ->where('assignment_question.question_id', $rootNodeQuestion->id)
                ->first();

            $time_spent = $RemediationSubmission
                ->where('user_id', $request->user()->id)
                ->where('assignment_id', $assignment->id)
                ->where('learning_tree_id', $learningTree->id);

            if ($assignment_question_learning_tree->learning_tree_success_level === 'branch') {
                $time_spent = $time_spent->where('branch_id', $branch_id);
            }
            $time_spent = $time_spent->sum('time_spent');

            $min_time = $assignment_question_learning_tree->min_time * 60;
            $time_left = max($min_time - $time_spent, 0);

            $response['learning_tree_success_criteria_time_left'] = $time_left * 1000; //in milliseconds for the countdown component
            $response['type'] = 'success';

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to initialize the time for your Learning Tree.  Please try again or contact us for assistance.";
        }
        return $response;


    }

    /**
     * @param Request $request
     * @param Assignment $assignment
     * @param LearningTree $learningTree
     * @param int $branch_id
     * @param Question $question
     * @param RemediationSubmission $RemediationSubmission
     * @return array
     * @throws Exception
     */
    public function updateTimeSpent(Request               $request,
                                    Assignment            $assignment,
                                    LearningTree          $learningTree,
                                    int                   $branch_id,
                                    Question              $question,
                                    RemediationSubmission $RemediationSubmission): array
    {
        $response['type'] = 'error';
        try {
            $remediationSubmission = $RemediationSubmission
                ->where('user_id', $request->user()->id)
                ->where('assignment_id', $assignment->id)
                ->where('learning_tree_id', $learningTree->id)
                ->where('branch_id', $branch_id)
                ->where('question_id', $question->id)
                ->first();
            if ($remediationSubmission) {
                DB::table('remediation_submissions')
                    ->where('user_id', $request->user()->id)
                    ->where('assignment_id', $assignment->id)
                    ->where('question_id', $question->id)
                    ->update(['time_spent'=>$remediationSubmission->time_spent + 3]);
            } else {
                $remediationSubmission = new RemediationSubmission();
                $remediationSubmission->user_id = $request->user()->id;
                $remediationSubmission->assignment_id = $assignment->id;
                $remediationSubmission->learning_tree_id = $learningTree->id;
                $remediationSubmission->branch_id = $branch_id;
                $remediationSubmission->question_id = $question->id;
                $remediationSubmission->proportion_correct = null;
                $remediationSubmission->time_spent = 3;
                $remediationSubmission->save();
            }

            $response['time_spent'] = "$remediationSubmission->time_spent seconds";
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to add the time spent in the Learning Tree.  Please try again or contact us for assistance.";
        }
        return $response;


    }
}
