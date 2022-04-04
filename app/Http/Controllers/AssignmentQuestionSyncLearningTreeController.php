<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\AssignmentQuestionLearningTree;
use App\AssignmentSyncQuestion;
use App\BetaCourseApproval;
use App\Http\Requests\LearningTreeRubric;
use App\Question;
use App\LearningTree;
use App\RemediationSubmission;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

use App\Exceptions\Handler;
use \Exception;

class AssignmentQuestionSyncLearningTreeController extends Controller
{

    /**
     * @param LearningTreeRubric $request
     * @param Assignment $assignment
     * @param Question $question
     * @param AssignmentSyncQuestion $assignmentSyncQuestion
     * @return array
     * @throws Exception
     */
    public function update(LearningTreeRubric     $request,
                           Assignment             $assignment,
                           Question               $question,
                           AssignmentSyncQuestion $assignmentSyncQuestion): array
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('update', [$assignmentSyncQuestion, $assignment]);
        $data = $request->validated();

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        $assignment_question = DB::table('assignment_question')
            ->where('assignment_id', $assignment->id)
            ->where('question_id', $question->id)
            ->first();

        try {
            if ($data['learning_tree_success_level'] === 'tree'){
                $data['number_of_successful_branches_for_a_reset'] = null;
                $data['number_of_resets'] = 1;
            }
            DB::table('assignment_question_learning_tree')
                ->where('assignment_question_id', $assignment_question->id)
                ->update($data);
            $response['type'] = 'success';
            $response['message'] = "The Learning Tree rubric has been updated.";
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating the Learning Tree rubric.  Please try again or contact us for assistance.";
        }

        return $response;


    }

    /**
     * @param Request $request
     * @param Assignment $assignment
     * @param Question $question
     * @param LearningTree $learningTree
     * @param RemediationSubmission $remediationSubmission
     * @param AssignmentQuestionLearningTree $assignmentQuestionLearningTree
     * @return array
     * @throws Exception
     */
    public function getAssignmentQuestionLearningTreeInfo(Request                        $request,
                                                          Assignment                     $assignment,
                                                          Question                       $question,
                                                          LearningTree                   $learningTree,
                                                          RemediationSubmission          $remediationSubmission,
                                                          AssignmentQuestionLearningTree $assignmentQuestionLearningTree): array
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('view', $assignment);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {

            $assignment_question_learning_tree= $assignmentQuestionLearningTree->getAssignmentQuestionLearningTreeByRootNodeQuestionId($assignment->id, $question->id);
            $learningTree = $learningTree->where('id', $assignment_question_learning_tree->learning_tree_id)->first();
            $learning_tree_branch_structure = $learningTree->getBranchStructure();
            $branch_and_twig_info = $learningTree->getBranchAndTwigInfo($learning_tree_branch_structure);
            $branch_and_twig_info = $remediationSubmission->getLearningTreeBranchAndTwigWithSuccessInfo($assignment_question_learning_tree, $branch_and_twig_info, $assignment, $request->user()->id, $assignment_question_learning_tree->learning_tree_id);

            $can_resubmit_root_node_question = $remediationSubmission->canResubmitRootNodeQuestion($assignment_question_learning_tree, $request->user()->id, $assignment->id, $learningTree->id);
            //get number of branches
            //get number of assessments on each branch
            $response['can_resubmit_root_node_question'] = $can_resubmit_root_node_question;
            $response['assignment_question_learning_tree_info'] = $assignment_question_learning_tree;
            $response['branch_and_twig_info'] = $branch_and_twig_info;
            $response['type'] = 'success';
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the Learning Tree information for this assignment question.  Please try again or contact us for assistance.";
        }

        return $response;

    }

    /**
     * @param Assignment $assignment
     * @param LearningTree $learningTree
     * @param AssignmentSyncQuestion $assignmentSyncQuestion
     * @param Question $Question
     * @param BetaCourseApproval $betaCourseApproval
     * @return array
     * @throws Exception
     */
    public function store(Assignment             $assignment,
                          LearningTree           $learningTree,
                          AssignmentSyncQuestion $assignmentSyncQuestion,
                          Question               $Question,
                          BetaCourseApproval     $betaCourseApproval)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('add', [$assignmentSyncQuestion, $assignment]);

        if (!$authorized->allowed()) {

            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $question_id = $Question->getQuestionIdsByPageId($learningTree->root_node_page_id, $learningTree->root_node_library, false)[0];
            $in_assignment = DB::table('assignment_question')->where('assignment_id', $assignment->id)
                ->where('question_id', $question_id)->get()->isNotEmpty();
            if ($in_assignment) {
                $response['message'] = 'That Learning Tree is already in the assignment.';
                return $response;

            }

            DB::beginTransaction();
            DB::table('assignment_question')
                ->insert([
                    'assignment_id' => $assignment->id,
                    'question_id' => $question_id,
                    'order' => $assignmentSyncQuestion->getNewQuestionOrder($assignment),
                    'points' => $assignment->points_per_question === 'number of points'
                        ? $assignment->default_points_per_question
                        : 0, //don't need to test since tested already when creating an assignment
                    'weight' => $assignment->points_per_question === 'number of points' ? null : 1,
                    'open_ended_submission_type' => 0
                ]);
            $assignment_question_id = DB::getPdo()->lastInsertId();
            DB::table('assignment_question_learning_tree')
                ->insert([
                    'assignment_question_id' => $assignment_question_id,
                    'learning_tree_id' => $learningTree->id,
                    'learning_tree_success_level' => $assignment->learning_tree_success_level,
                    'learning_tree_success_criteria' => $assignment->learning_tree_success_criteria,
                    'number_of_successful_branches_for_a_reset' => $assignment->number_of_successful_branches_for_a_reset,
                    'number_of_resets' => $assignment->number_of_resets,
                    'min_time' => $assignment->min_time,
                    'min_number_of_successful_assessments' => $assignment->min_number_of_successful_assessments,
                    'free_pass_for_satisfying_learning_tree_criteria' => $assignment->free_pass_for_satisfying_learning_tree_criteria,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);
            $assignmentSyncQuestion->updatePointsBasedOnWeights($assignment);
            $betaCourseApproval->updateBetaCourseApprovalsForQuestion($assignment, $question_id, 'add', $learningTree->id);
            DB::commit();
            $response['type'] = 'success';
            $response['message'] = 'The Learning Tree has been added to the assignment.';
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error adding the Learning Tree to the assignment.  Please try again or contact us for assistance.";
        }

        return $response;

    }

}
