<?php

namespace App\Http\Controllers;

use App\AssignmentLevelOverride;
use App\AssignmentQuestionLearningTree;
use App\AssignmentSyncQuestion;
use App\DataShop;
use App\Discussion;
use App\DiscussionComment;
use App\Exceptions\Handler;
use App\Http\Requests\UpdateScoresRequest;
use App\LearningTree;
use App\LearningTreeNodeSubmission;
use App\QuestionLevelOverride;
use App\SubmissionHistory;
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
use Illuminate\Support\Facades\Storage;
use Throwable;

class SubmissionController extends Controller
{
    use GeneralSubmissionPolicy;

    /**
     * @param Request $request
     * @param Assignment $assignment
     * @param Question $question
     * @param Submission $submission
     * @return array
     * @throws Exception
     */
    public function submitWork(Request    $request,
                               Assignment $assignment,
                               Question   $question,
                               Submission $submission)
    {

        try {
            $response['type'] = 'error';
            $submitted_work = $request->submitted_work;
            $authorized = Gate::inspect('submitWork', [$submission, $assignment, $assignment->id, $question->id]);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            if (Storage::disk('s3')->exists("$submitted_work")) {
                $submission->where('user_id', $request->user()->id)
                    ->where('assignment_id', $assignment->id)
                    ->where('question_id', $question->id)
                    ->update(['submitted_work' => pathinfo($submitted_work, PATHINFO_BASENAME),
                        'submitted_work_at' => now()]);
                $response['message'] = "Your work has been submitted.";
                $response['type'] = 'success';
                $response['submitted_work_url'] = Storage::disk('s3')->temporaryUrl($submitted_work, now()->addDay());
                $response['submitted_work_at'] = Carbon::now('UTC') // Get the current UTC time
                ->setTimezone($request->user()->time_zone) // Adjust to the user's timezone
                ->format('M d, Y \a\t g:i:s a');

            } else {
                $response['message'] = "We were unable to locate the submitted work on the server.  Please try again or contact us for assistance.";
            }
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to save your submitted work.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    public function deleteSubmittedtWork(Request    $request,
                                         Assignment $assignment,
                                         Question   $question,
                                         Submission $submission)
    {

        try {
            $response['type'] = 'error';
            $authorized = Gate::inspect('deleteSubmittedWork', [$submission, $assignment, $assignment->id, $question->id]);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            $submission->where('user_id', $request->user()->id)
                ->where('assignment_id', $assignment->id)
                ->where('question_id', $question->id)
                ->update(['submitted_work' => null,
                    'submitted_work_at' => null]);
            $response['message'] = "Your submitted work has been deleted.";
            $response['type'] = 'info';

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to delete your submitted work.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    /**
     * @param Request $request
     * @param Question $question
     * @param AssignmentSyncQuestion $assignmentSyncQuestion
     * @param Assignment $assignment
     * @param Submission $submission
     * @return array
     * @throws Exception
     */
    public function submissionExistsInCurrentCourseByOwnerAndQuestion(Request                $request,
                                                                      Question               $question,
                                                                      AssignmentSyncQuestion $assignmentSyncQuestion,
                                                                      Assignment             $assignment,
                                                                      Submission             $submission): array
    {
        try {
            $response['type'] = 'error';
            $authorized = Gate::inspect('submissionExistsInCurrentCourseByOwnerAndQuestion', $submission);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            $assignment_ids_with_the_question = $assignmentSyncQuestion->getAssignmentIdsWithTheQuestion($question);
            //get courses with assignment_ids with the question ---- but not open


            $open_assignment_ids = $assignment->getOpenAssignmentIdsFromSubsetOfAssignmentIds($assignment_ids_with_the_question);
            $open_assignment_ids_in_owner_course = $assignment->openAssignmentIdsInOwnerCourse($request->user(), $open_assignment_ids);
            $response['submissions_exist'] = $this->_submissionsExistByAssignmentIds($open_assignment_ids_in_owner_course);
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = $e->getMessage();
        }
        return $response;

    }

    /**
     * @param array $open_assignment_ids_in_owner_course
     * @return bool
     */
    private function _submissionsExistByAssignmentIds(array $open_assignment_ids_in_owner_course): bool
    {
        foreach (['submissions', 'submission_files'] as $table) {
            if (DB::table($table)
                ->join('users', "$table.user_id", '=', 'users.id')
                ->whereIn('assignment_id', $open_assignment_ids_in_owner_course)
                ->where('fake_student', 0)
                ->where('formative_student', 0)
                ->where('role', 3)
                ->exists()) {
                return true;
            }
            if (DB::table('discussions')
                ->join('discussion_comments', 'discussions.id', '=', 'discussion_comments.discussion_id')
                ->join('users', "discussion_comments.user_id", '=', 'users.id')
                ->whereIn('assignment_id', $open_assignment_ids_in_owner_course)
                ->where('fake_student', 0)
                ->where('formative_student', 0)
                ->where('role', 3)
                ->exists()) {
                return true;
            }
            return false;
        }


    }

    /**
     * @param Request $request
     * @param Assignment $assignment
     * @param Question $question
     * @param Submission $Submission
     * @return array
     * @throws Exception
     */
    public function submissionArray(Request    $request,
                                    Assignment $assignment,
                                    Question   $question,
                                    Submission $Submission): array
    {

        try {
            $response['type'] = 'error';
            $authorized = Gate::inspect('submissionArray', [$Submission, $assignment]);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            $submission_history_id = $request->submission_history_id;
            $user_id = $request->user_id;
            $submission = $Submission->where('user_id', $user_id)
                ->where('assignment_id', $assignment->id)
                ->where('question_id', $question->id)
                ->first();
            if (!$submission) {
                $response['message'] = "There is no submission associated with this question.";
                return $response;
            }

            if ($submission_history_id) {
                $submission = SubmissionHistory::find($submission_history_id);
            }
            $submission_array = (new Submission())->getSubmissionArray($assignment, $question, $submission, false);
            $response['type'] = 'success';
            $response['submission_array'] = $submission_array;
            $response['show_correct_answer'] = isset($submission_array[0]) && isset($submission_array[0]['correct_ans']);
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
    function submissionPieChartData(Assignment             $assignment,
                                    Question               $question,
                                    Submission             $submission,
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
            $choices_by_identifier = [];
            $counts_by_identifier = [];
            foreach ($submission_results as $value) {
                $submission = json_decode($value->submission, true);
                //Log::info(print_r($submission, true));

                $technology = $value->technology;
                switch ($technology) {
                    case('qti'):
                        $question_type = $submission['question']['questionType'] ?? null;
                        if (!in_array($question_type, ['true_false', 'multiple_choice'])) {
                            $response['message'] = 'Native questions only support True/False and Multiple Choice.';
                            return $response;

                        }
                        switch ($question_type) {
                            case('true_false'):
                                if (!$choices_by_identifier) {
                                    $choices = ['True', 'False'];
                                    $counts = [0, 0];
                                    foreach ($submission['question']['simpleChoice'] as $key => $choice) {
                                        $choices_by_identifier[$choice['identifier']] = $choice['value'];
                                    }
                                }
                                if (!isset($response['correct_answer'])) {
                                    foreach ($submission['question']['simpleChoice'] as $key => $choice) {
                                        if ($choice['correctResponse']) {
                                            $response['correct_answer'] = $choice['value'];
                                        }
                                    }
                                }
                                $choices_by_identifier[$submission['student_response']] === 'True'
                                    ? $counts[0]++
                                    : $counts[1]++;
                                break;

                            case('multiple_choice'):
                                if (!$choices_by_identifier) {
                                    foreach ($submission['question']['simpleChoice'] as $key => $choice) {
                                        $choices_by_identifier[$choice['identifier']] = $choice['value'];
                                        $counts_by_identifier[$choice['identifier']] = 0;
                                    }
                                }
                                if (!isset($response['correct_answer'])) {
                                    foreach ($submission['question']['simpleChoice'] as $key => $choice) {
                                        if ($choice['correctResponse']) {
                                            $response['correct_answer'] = $choice['value'];
                                        }
                                    }
                                }
                                foreach ($counts_by_identifier as $identifier => $count_by_identifier) {
                                    //Log::info($identifier . ' ' . $submission['student_response']);
                                    if (+$submission['student_response'] === +$identifier) {
                                        $counts_by_identifier[$identifier]++;
                                    }
                                }
                                break;
                        }

                        break;
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
                        $response['message'] = 'Only True/False or Multiple Choice Native/H5P questions are supported at this time.';
                        return $response;
                }
            }

            if (isset($technology) && isset($question_type) && $technology === 'qti' && $question_type === 'multiple_choice') {
                $choices = array_values($choices_by_identifier);
                $counts = array_values($counts_by_identifier);
            }
            $response['pie_chart_data']['labels'] = array_values($choices);
            $response['pie_chart_data']['datasets']['borderWidth'] = 1;
            $response['correct_answer_index'] = null;
            foreach ($choices as $key => $choice) {
                $percent = 90 - 10 * $key;
                $first = 60 - 20 * $key;
                $response['pie_chart_data']['datasets']['backgroundColor'][$key] = "hsla($first, 85%, $percent%, 0.9)";
                if ($choice === $response['correct_answer']) {
                    $response['correct_answer_index'] = $key;
                }
            }

            $total = array_sum($counts);
            ksort($counts);
            if ($total) {
                foreach ($counts as $key => $count) {
                    $counts[$key] = Round(100 * $count / $total);
                }
            }
            foreach ($response['pie_chart_data']['labels'] as $key => $label) {
                $response['pie_chart_data']['labels'][$key] .= "  &mdash; $counts[$key]%";
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

    public
    function getCorrectAnswer($technology, $object, $correct_answer_index)
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
            $tables = ['submissions',
                'h5p_activity_sets',
                'submission_files',
                'seeds',
                'can_give_ups',
                'shown_hints',
                'submission_histories'];
            foreach ($tables as $table) {
                DB::table($table)
                    ->where('user_id', $request->user()->id)
                    ->where('assignment_id', $assignment->id)
                    ->where('question_id', $question->id)
                    ->delete();
            }
            $discussion_comments = DB::table('discussions')
                ->join('discussion_comments', 'discussion_comments.discussion_id', '=', 'discussions.id')
                ->where('discussion_comments.user_id', $request->user()->id)
                ->where('assignment_id', $assignment->id)
                ->where('question_id', $question->id)
                ->select('discussion_comments.id')
                ->get();
            foreach ($discussion_comments as $discussion_comment) {
                DiscussionComment::find($discussion_comment->id)->delete();
            }
            $discussions = Discussion::where('user_id', $request->user()->id)
                ->where('assignment_id', $assignment->id)
                ->where('question_id', $question->id)
                ->get();
            foreach ($discussions as $discussion) {
                if (!DB::table('discussion_comments')
                    ->where('discussion_id', $discussion->id)
                    ->first()) {
                    $discussion->delete();
                }
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
