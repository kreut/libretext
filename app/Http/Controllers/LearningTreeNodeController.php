<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\AssignmentLevelOverride;
use App\AssignmentQuestionLearningTree;
use App\Exceptions\Handler;
use App\LearningTree;
use App\LearningTreeNode;
use App\LearningTreeReset;
use App\Question;
use App\QuestionLevelOverride;
use App\Score;
use App\Submission;
use App\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class LearningTreeNodeController extends Controller
{
    /**
     * @param Request $request
     * @param Assignment $assignment
     * @param Question $question
     * @param LearningTreeNode $learningTreeNode
     * @param AssignmentQuestionLearningTree $assignmentQuestionLearningTree
     * @param LearningTreeReset $learningTreeReset
     * @return array
     * @throws Exception
     */
    public function resetRootNodeSubmission(Request                        $request,
                                            Assignment                     $assignment,
                                            Question                       $question,
                                            LearningTreeNode               $learningTreeNode,
                                            AssignmentQuestionLearningTree $assignmentQuestionLearningTree,
                                            LearningTreeReset              $learningTreeReset): array
    {
        try {
            $response['type'] = 'error';


            $authorized = Gate::inspect('resetRootNodeSubmission', [$learningTreeNode, $assignment, $assignment->id, $question->id]);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }

            $can_reset_root_node_submission = $learningTreeReset->canResetRootNodeSubmission($assignment, $question, request()->user());
            if (!$can_reset_root_node_submission) {
                $response['type'] = 'info';
                $response['message'] = 'Since this assignment is past due, you cannot reset the original submission.';
                return $response;
            }

            $submission = Submission::where('user_id', $request->user()->id)
                ->where('assignment_id', $assignment->id)
                ->where('question_id', $question->id)
                ->first();
            $assignment_question_learning_tree = $assignmentQuestionLearningTree->getAssignmentQuestionLearningTreeByRootNodeQuestionId($assignment->id, $question->id);

            $learningTreeReset = LearningTreeReset::where('user_id', $request->user()->id)
                ->where('assignment_id', $assignment->id)
                ->where('learning_tree_id', $assignment_question_learning_tree->learning_tree_id)
                ->first();
            $number_resets_available = 0;
            DB::beginTransaction();
            if ($learningTreeReset) {
                $number_resets_available = max($learningTreeReset->number_resets_available - 1, 0);
                $learningTreeReset->number_resets_available = $number_resets_available;
                $learningTreeReset->save();
            }
            if ($submission) {
                $assignment_score = Score::where('assignment_id', $assignment->id)
                    ->where('user_id', $request->user()->id)
                    ->select('id', 'score')
                    ->first();
                if ($assignment_score) {
                    $assignment_score->score = $assignment_score->score - $submission->score;
                    $assignment_score->save();
                }
                $submission->delete();
                DB::table('seeds')
                    ->where('user_id', $request->user()->id)
                    ->where('assignment_id', $assignment->id)
                    ->where('question_id', $question->id)
                    ->delete();


            }
            $response['type'] = 'info';
            $response['message'] = 'The submission has been reset and you may resubmit.';
            $response['number_resets_available'] = $number_resets_available;
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error removing the root node submission.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    public function getMetaInfo(Request      $request,
                                LearningTree $learning_tree,
                                int          $question_id): array
    {

        $response['type'] = 'error';
        $question = DB::table('questions')->where('id', $question_id)->first();
        if (!$question) {
            $response['message'] = "A question with question ID '$question_id' does not exist.";
            return $response;
        }
        try {
            $last_learning_outcome = DB::table('learning_tree_node_learning_outcome')
                ->join('learning_outcomes',
                    'learning_tree_node_learning_outcome.learning_outcome_id', '=', 'learning_outcomes.id')
                ->where('user_id', $request->user()->id)
                ->select('subject')
                ->orderBy('learning_tree_node_learning_outcome.id', 'desc')
                ->first();


            $learning_outcome = DB::table('learning_tree_node_learning_outcome')
                ->join('learning_outcomes',
                    'learning_tree_node_learning_outcome.learning_outcome_id', '=', 'learning_outcomes.id')
                ->where('user_id', $request->user()->id)
                ->where('learning_tree_id', $learning_tree->id)
                ->where('question_id', $question->id)
                ->first();

            //check to see if they have one
            $branch = DB::table('branches')
                ->where('user_id', $request->user()->id)
                ->where('learning_tree_id', $learning_tree->id)
                ->where('question_id', $question->id)
                ->first();
            if ($branch) {
                $response['description'] = $branch->description;
            } else {
                //try someone else's
                $branch = DB::table('branches')
                    ->where('learning_tree_id', $learning_tree->id)
                    ->where('question_id', $question->id)
                    ->first();
                $response['description'] = $branch ? $branch->description : '';
            }
            $learning_tree_node_description = DB::table('learning_tree_node_descriptions')
                ->where('learning_tree_id', $learning_tree->id)
                ->where('question_id', $question->id)
                ->where('user_id', $request->user()->id)
                ->first();

            $response['subject'] = $learning_outcome ? $learning_outcome->subject : ($last_learning_outcome ? $last_learning_outcome->subject : null);
            $response['learning_outcome'] = $learning_outcome ? ['id' => $learning_outcome->id, 'label' => $learning_outcome->description] : '';
            $response['title'] = $learning_tree_node_description && $learning_tree_node_description->title ? $learning_tree_node_description->title : $question->title;
            $response['notes'] = $learning_tree_node_description && $learning_tree_node_description->notes ? $learning_tree_node_description->notes : '';
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the node meta info.  Please try again or contact us for assistance.";
        }
        return $response;

    }


}
