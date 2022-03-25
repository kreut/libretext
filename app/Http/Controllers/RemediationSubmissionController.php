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
     * @param Question $question
     * @param RemediationSubmission $RemediationSubmission
     * @return array
     * @throws Exception
     */
    public function getTimeLeft(Request               $request,
                                Assignment            $assignment,
                                LearningTree          $learningTree,
                                Question              $question,
                                RemediationSubmission $RemediationSubmission): array
    {

        $response['type'] = 'error';
        /*$authorized = Gate::inspect('denyRefreshQuestion', $refreshQuestionRequest);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }*/
        try {
            $remediation_submission = $RemediationSubmission
                ->where('user_id', $request->user()->id)
                ->where('assignment_id', $assignment->id)
                ->where('learning_tree_id', $learningTree->id)
                ->where('question_id', $question->id)
                ->first();
            $time_left = $remediation_submission
                ? min($assignment->min_time * 60 - $remediation_submission->time_spent, 0)
                : $assignment->min_time * 60;

            $response['learning_tree_success_criteria_time_left'] = $time_left;
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
     * @param Question $question
     * @param RemediationSubmission $RemediationSubmission
     * @return array
     * @throws Exception
     */
    public function updateTimeSpent(Request               $request,
                                    Assignment            $assignment,
                                    LearningTree          $learningTree,
                                    Question              $question,
                                    RemediationSubmission $RemediationSubmission): array
    {
        $response['type'] = 'error';
        try {
            $remediationSubmission = $RemediationSubmission
                ->where('user_id', $request->user()->id)
                ->where('assignment_id', $assignment->id)
                ->where('learning_tree_id', $learningTree->id)
                ->where('question_id', $question->id)
                ->first();
            if ($remediationSubmission) {
                $remediationSubmission->time_spent = $remediationSubmission->time_spent + 3;
            } else {
                $remediationSubmission = new RemediationSubmission();
                $remediationSubmission->user_id = $request->user()->id;
                $remediationSubmission->assignment_id = $assignment->id;
                $remediationSubmission->learning_tree_id = $learningTree->id;
                $remediationSubmission->question_id = $question->id;
                $remediationSubmission->proportion_correct = null;
                $remediationSubmission->time_spent = 3;
            }
            $remediationSubmission->save();
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
