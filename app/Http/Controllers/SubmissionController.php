<?php

namespace App\Http\Controllers;

use App\AssignmentLevelOverride;
use App\AssignmentQuestionLearningTree;
use App\AssignmentSyncQuestion;
use App\DataShop;
use App\Exceptions\Handler;
use App\Http\Requests\UpdateScoresRequest;
use App\LearningTree;
use App\LearningTreeNodeSubmission;
use App\QuestionLevelOverride;
use Carbon\Carbon;
use Exception;
use App\Submission;
use App\Score;
use App\Assignment;
use App\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

use App\Http\Requests\StoreSubmission;
use App\Traits\GeneralSubmissionPolicy;
use Throwable;

class SubmissionController extends Controller
{
    use GeneralSubmissionPolicy;

    /**
     * @param Assignment $assignment
     * @param Question $question
     * @param Submission $Submission
     * @return array
     * @throws Exception
     */
    public function submissionArray(Assignment $assignment, Question $question, Submission $Submission): array
    {

        try {
            $response['type'] = 'error';
            $authorized = Gate::inspect('submissionArray', [$Submission, $assignment]);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            $submission = $Submission->where('user_id', request()->user()->id)
                ->where('assignment_id', $assignment->id)
                ->where('question_id', $question->id)
                ->first();
            if (!$submission) {
                $response['message'] = "There is no submission associated with this question.";
                return $response;
            }

            $submission_array = $submission->getSubmissionArray($assignment, $question, $submission);
            $response['type'] = 'success';
            $response['submission_array'] = $submission_array;
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
        }
        return $response;

    }

    /**
     * @param Request $request
     * @param Assignment $assignment
     * @param Question $question
     * @param Submission $submission
     * @param AssignmentLevelOverride $assignmentLevelOverride
     * @param QuestionLevelOverride $questionLevelOverride
     * @return array|void
     * @throws Exception
     */
    public function canSubmit(Request                 $request,
                              Assignment              $assignment,
                              Question                $question,
                              Submission              $submission,
                              AssignmentLevelOverride $assignmentLevelOverride,
                              QuestionLevelOverride   $questionLevelOverride)
    {
        try {
            $submission = $submission->where('assignment_id', $assignment->id)
                ->where('question_id', $question->id)
                ->where('user_id', $request->user()->id)
                ->first();

            if ($submission && $assignment->number_of_allowed_attempts !== 'unlimited'
                && (int)$submission->submission_count === (int)$assignment->number_of_allowed_attempts) {
                $response['type'] = 'error';
                $response['message'] = "You have submitted this question $submission->submission_count times and you are only allowed $assignment->number_of_allowed_attempts attempts so your submission is not accepted.";
                return $response;
            }
            if ($questionLevelOverride->hasAutoGradedOverride($assignment->id, $question->id, $assignmentLevelOverride) ||
                $questionLevelOverride->hasOpenEndedOverride($assignment->id, $question->id, $assignmentLevelOverride)) {
                $response['type'] = 'success';
                return $response;
            }
            return $this->canSubmitBasedOnGeneralSubmissionPolicy($request->user(), $assignment, $assignment->id, $question->id);

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
        }
    }

    /**
     * @throws Throwable
     */
    public function updateScores(UpdateScoresRequest $request,
                                 Assignment          $assignment,
                                 Question            $question,
                                 Submission          $submission,
                                 Score               $score): array
    {
        return $score->handleUpdateScores($request, $assignment, $question, $submission);

    }


    /**
     * @param StoreSubmission $request
     * @param Assignment $Assignment
     * @param Score $score
     * @param AssignmentSyncQuestion $assignmentSyncQuestion
     * @return array
     * @throws Exception
     */
    public
    function store(StoreSubmission        $request,
                   Assignment             $Assignment,
                   Score                  $score,
                   AssignmentSyncQuestion $assignmentSyncQuestion): array
    {

        if ($request->is_learning_tree_node) {
            $learningTreeNodeSubmission = new LearningTreeNodeSubmission();
            $learning_tree = LearningTree::find($request->learning_tree_id);
            return $learningTreeNodeSubmission->store($request, new AssignmentQuestionLearningTree(), $learning_tree, new DataShop());
        } else {
            $Submission = new Submission();
            return $Submission->store($request,
                new Submission(),
                $Assignment,
                $score,
                new DataShop(),
                $assignmentSyncQuestion);

        }
    }

    /**
     * @param Assignment $assignment
     * @param Question $question
     * @param Submission $submission
     * @param AssignmentSyncQuestion $assignmentSyncQuestion
     * @return array
     * @throws Exception
     */
    public
    function submissionPieChartData(Assignment $assignment,
                                    Question $question,
                                    Submission $submission,
                                    AssignmentSyncQuestion $assignmentSyncQuestion): array
    {
        $response['type'] = 'error';
        $response['redirect_question'] = false;
        $response['pie_chart_data'] = [];
        $authorized = Gate::inspect('submissionPieChartData', [$submission, $assignment, $question]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {

            $number_enrolled = $assignment->course->enrolledUsers()->count();


            $submission_results = DB::table('submissions')
                ->join('questions', 'submissions.question_id', '=', 'questions.id')
                ->where('submissions.assignment_id', $assignment->id)
                ->where('submissions.question_id', $question->id)
                //->where('submissions.user_id','<>', $fake_student_user_id)
                ->select('submission', 'technology')
                ->get();

            $choices = [];
            $counts = [];

            foreach ($submission_results as $key => $value) {
                $submission = json_decode($value->submission, true);
                //Log::info(print_r($submission, true));

                $technology = $value->technology;
                switch ($technology) {
                    case('h5p'):
                        $object = $submission['object'];
                        //Log::info(print_r($submission, true));
                        // Log::info($object['definition']['interactionType']);
                        switch ($object['definition']['interactionType']) {
                            case('choice'):
                                if (!$choices) {
                                    $choices = $this->getChoices($technology, $object['definition']);
                                    foreach ($choices as $choice) {
                                        $counts[] = 0;
                                    }

                                    $correct_answer_index = $object['definition']['correctResponsesPattern'][0];
                                    $response['correct_answer'] = $this->getCorrectAnswer($technology, $object['definition'], $correct_answer_index);
                                }
                                if (isset($submission['result']['response'])) {
                                    $h5p_response = $submission['result']['response'];
                                    $counts[$h5p_response]++;
                                    $response['counts'] = $counts;
                                }
                                break;
                            case('true-false'):
                                if (!$choices) {
                                    $choices = ['True', 'False'];
                                    $counts = [0, 0];
                                    $correct_answer_index = $object['definition']['correctResponsesPattern'][0] === 'true' ? 0 : 1;
                                    $response['correct_answer'] = $choices[$correct_answer_index];
                                }
                                if (isset($submission['result']['response'])) {
                                    $submission['result']['response'] === "true" ? $counts[0]++ : $counts[1]++;
                                    $response['counts'] = $counts;
                                }
                                break;
                        }
                        //Log::info(print_r($submission['result'], true));

                        break;
                    case('webwork'):
                        Log::info(print_r($submission, true));
                        break;
                    default:
                        $response['message'] = 'Only h5p is supported at this time.';
                        return $response;
                }
            }

            $response['pie_chart_data']['labels'] = array_values($choices);
            $response['pie_chart_data']['datasets']['borderWidth'] = 1;
            foreach ($choices as $key => $choice) {
                $percent = 90 - 10 * $key;
                $first = 197 - 20 * $key;
                $response['pie_chart_data']['datasets']['backgroundColor'][$key] = "hsla($first, 85%, ${percent}%, 0.9)";
            }

            $total = array_sum($counts);
            ksort($counts);
            if ($total) {
                foreach ($counts as $key => $count) {
                    $counts[$key] = Round(100 * $count / $total);
                }
            }
            $response['pie_chart_data']['datasets']['data'] = $counts;
            $number_submission_results = count($submission_results); //don't include Fake
            $response['response_percent'] = $number_enrolled ? Round(100 * $number_submission_results / $number_enrolled, 1) : 0;
            $response['type'] = 'success';


        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error retrieving the submissions.  Please refresh the page and try again or contact us for assistance.";
        }
        return $response;
    }

    public function getCorrectAnswer($technology, $object, $correct_answer_index)
    {
        $correct_answer = 'Could not determine.';
        switch ($technology) {
            case('h5p'):
                foreach ($object['choices'] as $choice) {
                    if ($choice['id'] === $correct_answer_index)
                        $correct_answer = trim(array_values($choice['description'])[0]);
                }
                break;
        }
        return $correct_answer;


    }

    public
    function getChoices($technology, $object)
    {
        $choices = [];
        switch ($technology) {
            case('h5p'):
                foreach ($object['choices'] as $choice) {
                    $choices[$choice['id']] = array_values($choice['description'])[0];
                }
                break;

        }
        ksort($choices);
        return $choices;
    }


    /**
     * @param Request $request
     * @param Assignment $assignment
     * @param Question $question
     * @param Submission $submission
     * @param AssignmentQuestionLearningTree $assignmentQuestionLearningTree
     * @return array
     * @throws Exception
     */
    public
    function resetSubmission(Request                        $request,
                             Assignment                     $assignment,
                             Question                       $question,
                             Submission                     $submission,
                             AssignmentQuestionLearningTree $assignmentQuestionLearningTree): array
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('reset', [$submission, $assignment::find($assignment->id), $question->id]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        $assignment_question_learning_tree = $assignmentQuestionLearningTree->getAssignmentQuestionLearningTreeByRootNodeQuestionId($assignment->id, $question->id);

        try {
            DB::beginTransaction();
            if ($assignment_question_learning_tree) {
                DB::table('learning_tree_node_submissions')
                    ->where('user_id', $request->user()->id)
                    ->where('assignment_id', $assignment->id)
                    ->where('learning_tree_id', $assignment_question_learning_tree->learning_tree_id)
                    ->delete();
            }
            $tables = ['submissions', 'h5p_video_interactions', 'submission_files', 'seeds'];
            foreach ($tables as $table) {
                DB::table($table)
                    ->where('user_id', $request->user()->id)
                    ->where('assignment_id', $assignment->id)
                    ->where('question_id', $question->id)
                    ->delete();
            }
            DB::commit();
            $response['message'] = $assignment->algorithmic
                ? "Resetting the submission and algorithmically generating a new question."
                : "Resetting the submission.";
            $response['type'] = 'info';
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error resetting the submission.  Please try again or contact us for assistance.";
        }
        return $response;
    }

}
