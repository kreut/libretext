<?php


namespace App\Traits;

use App\Assignment;
use App\AssignmentQuestionLearningTree;
use App\LearningTreeSuccessfulBranch;
use App\Submission;
use Exception;
use Illuminate\Support\Facades\DB;


trait LearningTreeSuccessCriteria
{
    /**
     * @param bool $successful_branch
     * @param int $user_id
     * @param int $assignment_id
     * @param int $learning_tree_id
     * @param int $branch_id
     * @param int $root_node_question_id
     * @param $assignment_question_learning_tree
     * @return array
     * @throws Exception
     */
    public function getSuccessfulBranchInfo(bool $successful_branch,
                                            int  $user_id,
                                            int  $assignment_id,
                                            int  $learning_tree_id,
                                            int  $branch_id,
                                            int  $root_node_question_id,
                                                 $assignment_question_learning_tree

    ): array
    {

        $learningTreeSuccessfulBranch = new LearningTreeSuccessfulBranch();
        $successful_branch_exists = $successful_branch
            ? $learningTreeSuccessfulBranch->createIfNotExists($user_id, $assignment_id, $learning_tree_id, $branch_id)
            : false;

        $add_reset = false;
        $success = true;
        $num_successful_branches_for_a_reset = $assignment_question_learning_tree->number_of_successful_branches_for_a_reset;
        $plural = $num_successful_branches_for_a_reset > 1 ? "es" : '';
        $current_number_of_successful_branches_not_yet_reset = $learningTreeSuccessfulBranch->currentNumberOfSuccessfulBranchesNotYetReset(
            $user_id,
            $assignment_id,
            $assignment_question_learning_tree->learning_tree_id);
        $number_successful_branches_needed_for_a_reset = $assignment_question_learning_tree->number_of_successful_branches_for_a_reset - $current_number_of_successful_branches_not_yet_reset;
        if ($successful_branch) {
            if (!$successful_branch_exists) {
                $plural = $current_number_of_successful_branches_not_yet_reset > 1 ? "es" : '';
                $message = "You have successfully completed $current_number_of_successful_branches_not_yet_reset branch$plural ";
                $Submission = new Submission();
                $reset_count = $Submission
                    ->where('user_id', $user_id)
                    ->where('assignment_id', $assignment_id)
                    ->where('question_id', $root_node_question_id)
                    ->first()
                    ->reset_count;
                $too_many_resets = $Submission->tooManyResets(Assignment::find($assignment_id), $assignment_question_learning_tree->assignment_question_id, $assignment_question_learning_tree->learning_tree_id, $user_id, $reset_count, 'greater than');
                if ($too_many_resets) {
                    $message .= "but already have used up all of your resets.";
                } else {
                    $plural = $number_successful_branches_needed_for_a_reset > 1 ? 'es' : '';
                    $message .= $number_successful_branches_needed_for_a_reset
                        ? "and still need to complete $number_successful_branches_needed_for_a_reset branch$plural."
                        : "and will receive a reset for the <a id='show-root-assessment' style='cursor: pointer;'>Root Assessment</a>.";
                }

                $traffic_light_color = $too_many_resets
                    ? "yellow"
                    : "green";
                $add_reset = !$number_successful_branches_needed_for_a_reset && !$too_many_resets;
            } else {
                $message = "<br><br>However, you have already completed this branch, so you will not receive a reset for completing this assessment.";
                $traffic_light_color = "yellow";
            }
        } else {
            $success = false;
            $message = "You have successfully completed $current_number_of_successful_branches_not_yet_reset$plural and need to complete $number_successful_branches_needed_for_a_reset more before you can have a reset for the Root Assessment.";
            $traffic_light_color = "yellow";
        }
        return compact('success',
            'message',
            'add_reset',
            'traffic_light_color',
            'number_successful_branches_needed_for_a_reset',
            'successful_branch_exists');
    }

    /**
     * @throws Exception
     */
    public function updateReset(string $level,
                                bool   $learning_tree_success_criteria_satisfied,
                                       $successful_branch_exists,
                                int    $number_successful_branches_needed_for_a_reset,
                                int    $user_id,
                                int    $assignment_id,
                                int    $root_node_question_id)
    {
        $reset_count = false;
        switch ($level) {
            case('branch'):
                if ($learning_tree_success_criteria_satisfied
                    && !$successful_branch_exists
                    && !$number_successful_branches_needed_for_a_reset) {
                    // dd('reset');
                    $reset_count = DB::raw('reset_count + 1');
                }
                break;
            case('tree'):
                $reset_count = $learning_tree_success_criteria_satisfied ? 1 : 0;
                break;
            default:
                throw new Exception('Not a valid level');

        }
        if ($reset_count) {
            $assignmentQuestionLearningTree = new AssignmentQuestionLearningTree();
            $assignment_question_learning_tree = $assignmentQuestionLearningTree->getAssignmentQuestionLearningTreeByRootNodeQuestionId($assignment_id, $root_node_question_id);
            DB::table('submissions')
                ->where('user_id', $user_id)
                ->where('assignment_id', $assignment_id)
                ->where('question_id', $root_node_question_id)
                ->update(['submission_count' => 0,
                    'reset_count' => $reset_count]);
            DB::table('learning_tree_successful_branches')
                ->where('user_id', $user_id)
                ->where('assignment_id', $assignment_id)
                ->where('learning_tree_id', $assignment_question_learning_tree->learning_tree_id)
                ->update(['applied_to_reset' => 1]);
        }

    }


}
