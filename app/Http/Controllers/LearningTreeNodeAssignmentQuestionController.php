<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\Exceptions\Handler;
use App\JWE;
use App\LearningTree;
use App\LearningTreeNodeAssignmentQuestion;
use App\LearningTreeNodeDescription;
use App\LearningTreeNodeSeed;
use App\LearningTreeNodeSubmission;
use App\Policies\LearningTreeNodeAssignmentQuestionPolicy;
use App\Question;
use App\Traits\IframeFormatter;
use App\Traits\Seed;
use DOMDocument;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class LearningTreeNodeAssignmentQuestionController extends Controller
{
    use IframeFormatter;
    use Seed;

    /**
     * @param Request $request
     * @param Assignment $assignment
     * @param LearningTree $learningTree
     * @param Question $nodeQuestion
     * @param LearningTreeNodeAssignmentQuestion $learningTreeNodeAssignmentQuestion
     * @return array
     * @throws Exception
     */
    public function giveCreditForCompletion(Request                            $request,
                                            Assignment                         $assignment,
                                            LearningTree                       $learningTree,
                                            Question                           $nodeQuestion,
                                            LearningTreeNodeAssignmentQuestion $learningTreeNodeAssignmentQuestion): array
    {
        try {
            $response['type'] = 'error';
            $authorized = Gate::inspect('giveCreditForCompletion', [$learningTreeNodeAssignmentQuestion,
                $assignment->id,
                $learningTree,
                $nodeQuestion]);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }

            LearningTreeNodeSubmission::updateOrCreate([
                'user_id' => $request->user()->id,
                'assignment_id' => $assignment->id,
                'learning_tree_id' => $learningTree->id,
                'question_id' => $nodeQuestion->id],
                ['completed' => 1]);
            $response['type'] = 'success';
            $response['message'] = "The learning tree node has been completed.";

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error giving you credit for this learning tree node.  Please try again or contact us for assistance.";

        }

        return $response;

    }

    /**
     * @param Request $request
     * @param Assignment $assignment
     * @param LearningTree $learningTree
     * @param Question $nodeQuestion
     * @param LearningTreeNodeSubmission $learningTreeNodeSubmission
     * @param LearningTreeNodeSeed $learningTreeNodeSeed
     * @param LearningTreeNodeAssignmentQuestion $learningTreeNodeAssignmentQuestion
     * @return array
     * @throws Exception
     */
    public
    function show(Request                            $request,
                  Assignment                         $assignment,
                  LearningTree                       $learningTree,
                  Question                           $nodeQuestion,
                  LearningTreeNodeSubmission         $learningTreeNodeSubmission,
                  LearningTreeNodeSeed               $learningTreeNodeSeed,
                  LearningTreeNodeAssignmentQuestion $learningTreeNodeAssignmentQuestion): array
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('show', [$learningTreeNodeAssignmentQuestion, $assignment->id, $learningTree, $nodeQuestion]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $question = new Question();

            $node_question_result = $question->formatQuestionFromDatabase($request, $nodeQuestion);
            $nodeQuestion = $question->fill($node_question_result);
            if ($nodeQuestion->technology === 'text' || $nodeQuestion->assessment_type === 'exposition') {
                $min_number_of_minutes_in_exposition_node = $request->user()->fake_student || $request->user()->role !== 3
                    ? 5 * 1000
                    : $assignment->min_number_of_minutes_in_exposition_node * 60 * 1000;
                $nodeQuestion->time_left = $min_number_of_minutes_in_exposition_node;
            }
            $seed = '';
            if (in_array($question->technology, ['webwork', 'imathas', 'qti'])) {
                $learning_tree_node_seed = $learningTreeNodeSeed->where('user_id', $request->user()->id)
                    ->where('assignment_id', $assignment->id)
                    ->where('learning_tree_id', $learningTree->id)
                    ->where('question_id', $nodeQuestion->id)
                    ->first();
                if ($learning_tree_node_seed) {
                    $seed = $learning_tree_node_seed->seed;
                } else {
                    $seed = $this->createSeedByTechnologyAssignmentAndQuestion($assignment, $question);
                    $learningTreeNodeSeed->user_id = $request->user()->id;
                    $learningTreeNodeSeed->assignment_id = $assignment->id;
                    $learningTreeNodeSeed->learning_tree_id = $learningTree->id;
                    $learningTreeNodeSeed->question_id = $nodeQuestion->id;
                    $learningTreeNodeSeed->seed = $seed;
                    $learningTreeNodeSeed->save();
                }
            }
            $domd = new DOMDocument();
            $JWE = new JWE();
            $extra_custom_claims['is_learning_tree_node'] = true;
            $extra_custom_claims['learning_tree_id'] = $learningTree->id;
            //$extra_custom_claims['branch_id'] = $branch_id;

            $technology_src_and_problemJWT = $question->getTechnologySrcAndProblemJWT($request, $assignment, $nodeQuestion, $seed, true, $domd, $JWE, $extra_custom_claims);
            $technology_src = $technology_src_and_problemJWT['technology_src'];
            $problemJWT = $technology_src_and_problemJWT['problemJWT'];

            if ($technology_src) {
                $iframe_id = $this->createIframeId();
                //don't return if not available yet!
                $nodeQuestion['technology_iframe_src'] = $this->formatIframeSrc($question['technology_iframe'], $iframe_id, $problemJWT);
            }
            $nodeQuestion['technology_iframe'] = '';//hide this from students since it has the path
            if ($nodeQuestion['non_technology_iframe_src']) {
                session()->put('canViewLocallySavedContents', "$nodeQuestion->id");
            }
            $learning_tree_node_submission = $learningTreeNodeSubmission
                ->where('user_id', $request->user()->id)
                ->where('assignment_id', $assignment->id)
                ->where('learning_tree_id', $learningTree->id)
                ->where('question_id', $nodeQuestion->id)
                ->first();

            $learning_tree_node_description = DB::table('learning_tree_node_descriptions')
                ->where('learning_tree_id', $learningTree->id)
                ->where('question_id', $nodeQuestion->id)
                ->first();
            $nodeQuestion->completed = $learning_tree_node_submission && $learning_tree_node_submission->completed;
            $nodeQuestion->learning_tree_node_submission_id = $learning_tree_node_submission ? $learning_tree_node_submission->id : null;
            $nodeQuestion->title = $learning_tree_node_description->title;
            $nodeQuestion->node_description = $learning_tree_node_description->description;
            $response['node_question'] = $nodeQuestion;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the node question.  Please try again or contact us for assistance.";

        }

        return $response;
    }

    /**
     * @param Request $request
     * @param Assignment $assignment
     * @param LearningTree $learningTree
     * @param LearningTreeNodeDescription $learningTreeNodeDescription
     * @return array
     * @throws Exception
     */
    public function learningTreeNodeCompletionInfo(Request                     $request,
                                                   Assignment                  $assignment,
                                                   LearningTree                $learningTree,
                                                   LearningTreeNodeDescription $learningTreeNodeDescription): array
    {

        $learning_tree_question_ids = $learningTree->questionIds();
        try {
            $learning_tree_node_submissions = DB::table('learning_tree_node_submissions')
                ->where('user_id', $request->user()->id)
                ->where('assignment_id', $assignment->id)
                ->where('learning_tree_id', $learningTree->id)
                ->get();
            $completed_learning_tree_nodes = [];
            foreach ($learning_tree_node_submissions as $learning_tree_node_submission) {
                if ($learning_tree_node_submission->completed) {
                    $completed_learning_tree_nodes[] = $learning_tree_node_submission->question_id;
                }
            }
            $completed_learning_tree_nodes_by_question_id = [];
            foreach ($learning_tree_question_ids as $question_id) {
                $completed_learning_tree_nodes_by_question_id[$question_id] = in_array($question_id, $completed_learning_tree_nodes) ? 'completed' : 'not-completed';
            }


            $learning_tree_node_parents = $learningTree->nodeParents();


            //get the node descriptions to show to the student
            $learning_tree_node_descriptions = $learningTreeNodeDescription->where('learning_tree_id', $learningTree->id)->get();
            $learning_tree_node_descriptions_by_question_id = [];
            foreach ($learning_tree_node_descriptions as $learning_tree_node_description) {
                $learning_tree_node_descriptions_by_question_id[$learning_tree_node_description->question_id] = $learning_tree_node_description->title;
            }
            $learning_tree_node_uncompleted_parent_node_titles_by_question_id = [];

            //add in the ones that have parents which are not completed but don't include the root question node
            foreach ($learning_tree_node_parents as $key => $question_ids) {
                $learning_tree_node_uncompleted_parent_node_titles_by_question_id[$key] = [];
                foreach ($question_ids as $question_id) {
                    if ($question_id !== $learningTree->root_node_question_id && !in_array($question_id, $completed_learning_tree_nodes)) {
                        $learning_tree_node_uncompleted_parent_node_titles_by_question_id[$key][] = $learning_tree_node_descriptions_by_question_id[$question_id] ?? 'No description provided';
                    }
                }
            }

            $response['learning_tree_node_uncompleted_parent_node_titles_by_question_id'] = $learning_tree_node_uncompleted_parent_node_titles_by_question_id;
            $response['learning_tree_node_completion_info'] = $completed_learning_tree_nodes_by_question_id;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = 'We could not get the learning tree node completion information.  Please try again or contact us for assistance.';

        }
        return $response;
    }
}
