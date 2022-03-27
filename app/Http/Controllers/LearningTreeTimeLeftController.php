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
use Illuminate\Support\Facades\Log;

class LearningTreeTimeLeftController extends Controller
{
    /**
     * @param Request $request
     * @param LearningTreeTimeLeft $learningTreeTimeLeft
     * @return array
     * @throws Exception
     */
    public function getTimeLeft(Request              $request,
                                LearningTreeTimeLeft $learningTreeTimeLeft): array
    {

        $response['type'] = 'error';
        $assignment_id = $request->assignment_id;
        $learning_tree_id = $request->learning_tree_id;
        $level = $request->level;
        $branch_id = $request->branch_id;
        /*$authorized = Gate::inspect('denyRefreshQuestion', $refreshQuestionRequest);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }*/
        try {

            $time_left = $learningTreeTimeLeft
                ->where('user_id', $request->user()->id)
                ->where('assignment_id', $assignment_id)
                ->where('learning_tree_id', $learning_tree_id)
                ->select('time_left');
            if ($level === 'branch') {
                $time_left = $time_left
                    ->where('branch_id', $branch_id);
            }
            $time_left = $time_left->first();
            if ($time_left !== null) {
                $time_left = $time_left->time_left;
            } else {
                $assignment_question_learning_tree = DB::table('assignment_question_learning_tree')
                    ->join('assignment_question', 'assignment_question_learning_tree.assignment_question_id', '=', 'assignment_question.id')
                    ->select('min_time', 'learning_tree_success_level')
                    ->where('assignment_question.assignment_id', $assignment_id)
                    ->where('assignment_question.question_id', $request->root_node_question_id)
                    ->first();
                $time_left = $assignment_question_learning_tree->min_time*60;

            }

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
     * @param LearningTreeTimeLeft $LearningTreeTimeLeft
     * @return array
     * @throws Exception
     */
    public
    function update(Request $request, LearningTreeTimeLeft $LearningTreeTimeLeft): array
    {
        $assignment_id = $request->assignment_id;
        $learning_tree_id = $request->learning_tree_id;
        $seconds = $request->seconds;
        $level = $request->level;
        $branch_id = $request->branch_id;
        try {
            $learningTreeTimeLeft = $LearningTreeTimeLeft
                ->where('user_id', $request->user()->id)
                ->where('assignment_id', $assignment_id)
                ->where('learning_tree_id', $learning_tree_id)
                ->where('level', $level);
            if ($branch_id) {
                $learningTreeTimeLeft = $learningTreeTimeLeft->where('branch_id', $branch_id);
            }
            $learningTreeTimeLeft = $learningTreeTimeLeft->first();
            if ($learningTreeTimeLeft) {
                $query = $learningTreeTimeLeft
                    ->where('user_id', $request->user()->id)
                    ->where('assignment_id', $assignment_id)
                    ->where('learning_tree_id', $learning_tree_id)
                    ->where('level', $level);
                if ($branch_id) {
                    $query = $query->where('branch_id', $branch_id);
                }
                $query->update(['time_left' => $seconds]);
            } else {
                $learningTreeTimeLeft = new LearningTreeTimeLeft();
                $learningTreeTimeLeft->user_id = $request->user()->id;
                $learningTreeTimeLeft->assignment_id = $assignment_id;
                $learningTreeTimeLeft->learning_tree_id = $learning_tree_id;
                $learningTreeTimeLeft->branch_id = $branch_id;
                $learningTreeTimeLeft->level = $level;
                $learningTreeTimeLeft->time_left = $seconds;
                $learningTreeTimeLeft->save();
            }

            $response['time_left'] = "$seconds seconds";
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to update the time left in the Learning Tree.  Please try again or contact us for assistance.";
        }
        return $response;


    }
}
