<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\AssignmentQuestionLearningTree;
use App\Exceptions\Handler;
use App\LearningTree;
use App\LearningTreeTimeLeft;
use App\Question;
use App\RemediationSubmission;
use App\LearningTreeSuccessfulBranch;
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
     * @param AssignmentQuestionLearningTree $assignmentQuestionLearningTree
     * @return array
     * @throws Exception
     */
    public function getTimeLeft(Request                        $request,
                                LearningTreeTimeLeft           $learningTreeTimeLeft,
                                AssignmentQuestionLearningTree $assignmentQuestionLearningTree): array
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
                $assignment_question_learning_tree = $assignmentQuestionLearningTree->getAssignmentQuestionLearningTreeByRootNodeQuestionId($assignment_id, $request->root_node_question_id);
                $time_left = $assignment_question_learning_tree->min_time * 60;
                if ($request->user()->fake_student) {
                    $time_left = $time_left / 10;
                }

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
        $question_id = $request->question_id;
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
                if ($seconds === 0 && $level === 'branch') {
                    $learningTreeSuccessfulBranch = new LearningTreeSuccessfulBranch();
                    $successful_branch = $learningTreeSuccessfulBranch->createIfNotExists($request->user()->id, $assignment_id, $learning_tree_id, $branch_id);
                    if ($successful_branch) {
                        DB::table('submissions')
                            ->where('user_id', $request->user()->id)
                            ->where('assignment_id', $assignment_id)
                            ->where('question_id', $question_id)
                            ->update(['submission_count' => 0,
                                'reset_count' => 0]);
                    }
                }
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
