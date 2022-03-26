<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\Exceptions\Handler;
use App\LearningTree;
use App\LearningTreeTimeLeft;
use App\Question;
use App\RemediationSubmission;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class LearningTreeTimeLeftController extends Controller
{
    /**
     * @param Request $request
     * @param Assignment $assignment
     * @param LearningTree $learningTree
     * @param int $branch_id
     * @param Question $rootNodeQuestion
     * @param LearningTreeTimeLeft $learningTreeTimeLeft
     * @return array
     * @throws Exception
     */
    public function getTimeLeft(Request              $request,
                                Assignment           $assignment,
                                LearningTree         $learningTree,
                                int                  $branch_id,
                                Question             $rootNodeQuestion,
                                LearningTreeTimeLeft $learningTreeTimeLeft): array
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
                ->select('min_time', 'learning_tree_success_level')
                ->where('assignment_question.assignment_id', $assignment->id)
                ->where('assignment_question.question_id', $rootNodeQuestion->id)
                ->first();

            $time_left = $learningTreeTimeLeft
                ->where('user_id', $request->user()->id)
                ->where('assignment_id', $assignment->id)
                ->where('learning_tree_id', $learningTree->id);

            if ($assignment_question_learning_tree->learning_tree_success_level === 'branch') {
                $time_left = $time_left->where('branch_id', $branch_id);
            }
            $time_left = $time_left->sum('time_left');

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
    public function update(Request $request, LearningTreeTimeLeft $LearningTreeTimeLeft): array
    {
        $assignment_id = $request->assignment_id;
        $learning_tree_id = $request->learning_tree_id;
        $time_left = $request->time_left;
        $response['type'] = 'error';
        try {
            $learningTreeTimeLeft = $LearningTreeTimeLeft
                ->where('user_id', $request->user()->id)
                ->where('assignment_id', $assignment_id)
                ->where('learning_tree_id', $learning_tree_id)
                ->where('level', 'tree')
                ->first();
            if ($learningTreeTimeLeft) {
                $learningTreeTimeLeft
                    ->where('user_id', $request->user()->id)
                    ->where('assignment_id', $assignment_id)
                    ->where('learning_tree_id', $learning_tree_id)
                    ->where('level', 'tree')
                    ->update(['time_left' => $time_left]);

            } else {
                $learningTreeTimeLeft = new LearningTreeTimeLeft();
                $learningTreeTimeLeft->user_id = $request->user()->id;
                $learningTreeTimeLeft->assignment_id = $assignment_id;
                $learningTreeTimeLeft->learning_tree_id = $learning_tree_id;
                $learningTreeTimeLeft->level = 'tree';
                $learningTreeTimeLeft->time_left = $time_left;
                $learningTreeTimeLeft->save();
            }

            $response['time_left'] = "$time_left seconds";
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to add the time spent in the Learning Tree.  Please try again or contact us for assistance.";
        }
        return $response;


    }
}
