<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\AssignmentQuestionLearningTree;
use App\Exceptions\Handler;
use App\LearningTreeTimeLeft;
use App\RemediationSubmission;
use App\Traits\LearningTreeSuccessCriteria;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class LearningTreeTimeLeftController extends Controller
{
    use LearningTreeSuccessCriteria;

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
        $root_node_question_id = $request->root_node_question_id;
        $user_id = $request->user()->id;
        $authorized = Gate::inspect('getTimeLeft', [$learningTreeTimeLeft, $assignment_id, $root_node_question_id]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {

            $time_left = $learningTreeTimeLeft
                ->where('user_id', $user_id)
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
                $assignment_question_learning_tree = $assignmentQuestionLearningTree->getAssignmentQuestionLearningTreeByRootNodeQuestionId($assignment_id, $root_node_question_id);
                $time_left = $assignment_question_learning_tree->min_time * 60;
                if ($request->user()->fake_student) {
                    $time_left = 15;
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
     * @param AssignmentQuestionLearningTree $assignmentQuestionLearningTree
     * @param RemediationSubmission $remediationSubmission
     * @return array
     * @throws Exception
     */
    public
    function update(Request                        $request,
                    LearningTreeTimeLeft           $LearningTreeTimeLeft,
                    AssignmentQuestionLearningTree $assignmentQuestionLearningTree,
                    RemediationSubmission          $remediationSubmission): array
    {


        $assignment_id = $request->assignment_id;
        $learning_tree_id = $request->learning_tree_id;
        $seconds = $request->seconds;
        $level = $request->level;
        $branch_id = $request->branch_id;
        $question_id = $request->question_id;
        $user = $request->user();
        $user_id = $user->id;
        $response['type'] = 'error';


        $authorized = Gate::inspect('update', [$LearningTreeTimeLeft, Assignment::find($assignment_id), $assignment_id, $question_id]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        $assignment_question_learning_tree = $assignmentQuestionLearningTree->getAssignmentQuestionLearningTreeByLearningTreeId($assignment_id, $learning_tree_id);

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
                $original_query = $query;
                DB::beginTransaction();
                $query->update(['time_left' => $seconds]);
                $successful_branch_exists = false;
                $number_successful_branches_needed_for_a_reset = 0;
                if ($seconds - 3 <= 0) {
                    $original_query->update(['time_left' => 0]);
                    if ($level === 'branch') {
                        $successful_branch_info = $this->getSuccessfulBranchInfo(true,
                            $request->user()->id,
                            $assignment_id,
                            $learning_tree_id,
                            $branch_id,
                            $question_id,
                            $assignment_question_learning_tree);
                        $message = $successful_branch_info['message'];
                        $add_reset = $successful_branch_info['add_reset'];
                        $traffic_light_color = $successful_branch_info['traffic_light_color'];
                        $number_successful_branches_needed_for_a_reset = $successful_branch_info['number_successful_branches_needed_for_a_reset'];
                        $successful_branch_exists = $successful_branch_info['successful_branch_exists'];
                        $can_resubmit_root_node_question['success'] = true;
                    } else {
                        $can_resubmit_root_node_question = $remediationSubmission->canResubmitRootNodeQuestion($assignment_question_learning_tree, $user_id, $assignment_id, $learning_tree_id);
                        $traffic_light_color = $can_resubmit_root_node_question['success']
                            ? 'green'
                            : 'yellow';
                        $message = $can_resubmit_root_node_question['message'];
                        $add_reset = true;
                    }

                    $this->updateReset($level, true,
                        $successful_branch_exists,
                        $number_successful_branches_needed_for_a_reset,
                        $user_id,
                        $assignment_id,
                        $question_id);

                    $response['message'] = $message;
                    $response['can_resubmit_root_node_question'] = $can_resubmit_root_node_question['success'];
                    $response['traffic_light_color'] = $traffic_light_color;
                    $response['learning_tree_message'] = true;
                    $response['add_reset'] = $add_reset;


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
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to update the time left in the Learning Tree.  Please try again or contact us for assistance.";
        }
        return $response;


    }
}
