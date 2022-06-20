<?php

namespace App\Http\Controllers;

use App\BetaAssignment;
use App\BetaCourseApproval;
use App\Enrollment;
use App\Exceptions\Handler;
use App\Helpers\Helper;
use App\Http\Requests\StartClickerAssessment;
use App\Http\Requests\UpdateAssignmentQuestionWeightRequest;
use App\Http\Requests\UpdateCompletionScoringModeRequest;
use App\Http\Requests\UpdateOpenEndedSubmissionType;
use App\JWE;
use App\Libretext;
use App\RandomizedAssignmentQuestion;
use App\MyFavorite;
use App\Solution;
use App\Traits\LibretextFiles;
use App\Traits\Statistics;
use App\User;
use Carbon\CarbonImmutable;
use DOMDocument;
use \Exception;

use Illuminate\Http\Request;
use App\Http\Requests\UpdateAssignmentQuestionPointsRequest;
use App\Assignment;
use App\Question;
use App\Submission;
use App\SubmissionFile;
use App\Extension;


use App\Traits\IframeFormatter;
use App\Traits\DateFormatter;
use App\AssignmentSyncQuestion;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

use App\Traits\S3;
use App\Traits\SubmissionFiles;
use App\Traits\GeneralSubmissionPolicy;
use App\Traits\LatePolicy;
use App\Traits\JWT;
use Carbon\Carbon;
use stdClass;


class AssignmentSyncQuestionController extends Controller
{

    use IframeFormatter;
    use DateFormatter;
    use GeneralSubmissionPolicy;
    use S3;
    use SubmissionFiles;
    use JWT;
    use LibretextFiles;
    use LatePolicy;
    use Statistics;

    public function updateIFrameProperties(Request                $request,
                                           Assignment             $assignment,
                                           Question               $question,
                                           AssignmentSyncQuestion $assignmentSyncQuestion)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('updateIFrameProperties', [$assignmentSyncQuestion, $assignment, $question]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $item = $request->item;
            if (!in_array($item, ['assignment', 'submission', 'attribution'])) {
                $response['message'] = "$item is not a valid iframe item.";
                return $response;
            }
            $column = "{$item}_information_shown_in_iframe";
            $current_value = DB::table('assignment_question')
                ->where('assignment_id', $assignment->id)
                ->where('question_id', $question->id)
                ->first()
                ->$column;

            DB::table('assignment_question')
                ->where('assignment_id', $assignment->id)
                ->where('question_id', $question->id)
                ->update([$column => !$current_value]);
            $response['type'] = $current_value ? 'info' : 'success';
            $current_value_text = $current_value ? 'will not' : 'will';
            $response['message'] = "The $item information $current_value_text be shown in the iframe.";


        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating the iframe properties.  Please try again or contact us for assistance.";
        }

        return $response;


    }

    /**
     * @param Assignment $assignment
     * @return array
     * @throws Exception
     */
    public function validateCanSwitchToOrFromCompiledPdf(Assignment $assignment): array
    {
        $response['type'] = 'error';
        try {
            $submission_files = DB::table('submission_files')
                ->join('users', 'submission_files.user_id', '=', 'users.id')
                ->where('fake_student', 0)
                ->where('assignment_id', $assignment->id)
                ->first();
            if ($submission_files) {
                $response['message'] = "Since students have already submitted responses, you can't switch this option.";
                return $response;
            }
            $response['type'] = 'success';

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error validating whether you can switch from a compiled PDF assignment.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    /**
     * @param Assignment $assignment
     * @return array
     * @throws Exception
     */
    public function validateCanSwitchToCompiledPdf(Assignment $assignment)
    {
        $response['type'] = 'error';
        try {
            $has_other_types = DB::table('assignment_question')->where('assignment_id', $assignment->id)
                ->where('open_ended_submission_type', '<>', 'file')
                ->where('open_ended_submission_type', '<>', '0')
                ->first();
            if ($has_other_types) {
                $response['message'] = 'If you would like to use the compiled PDF feature, please update your assessments so that they are all of type "file" or "none".';
                return $response;
            }

            $response['type'] = 'success';


        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error validating whether you can switch from a compiled PDF assignment.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    /**
     * @param Request $request
     * @param Assignment $assignment
     * @param AssignmentSyncQuestion $assignmentSyncQuestion
     * @param BetaCourseApproval $betaCourseApproval
     * @return array
     * @throws Exception
     */
    public function remixAssignmentWithChosenQuestions(Request                $request,
                                                       Assignment             $assignment,
                                                       AssignmentSyncQuestion $assignmentSyncQuestion,
                                                       BetaCourseApproval     $betaCourseApproval): array
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('remixAssignmentWithChosenQuestions', [$assignmentSyncQuestion, $assignment]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        if ($assignment->cannotAddOrRemoveQuestionsForQuestionWeightAssignment()) {
            $response['message'] = "You cannot access the remixer since there are already submissions and this assignment computes points using question weights.";
            return $response;
        }


        try {
            $chosen_questions = $request->chosen_questions;
            $assignment_questions = $assignment->questions->pluck('id')->toArray();
            switch ($request->question_source) {
                case('all_questions'):
                case('my_questions'):
                case('my_favorites'):
                    $belongs_to_assignment = false;
                    break;
                case('commons'):
                case('my_courses'):
                case('all_public_courses'):
                    $belongs_to_assignment = true;
                    break;
                default:
                    $response['message'] = "$request->question_source is not a valid question source.";
                    return $response;
            }
            DB::beginTransaction();
            foreach ($chosen_questions as $key => $question) {
                if (!in_array($question['question_id'], $assignment_questions)) {
                    $learning_tree_id = null;
                    if ($belongs_to_assignment) {
                        $assignment_question = DB::table('assignment_question')
                            ->where('assignment_id', $question['assignment_id'])
                            ->where('question_id', $question['question_id'])
                            ->first();
                        if (!$assignment_question) {
                            $response['message'] = "Question {$question['question_id']} does not belong to that assignment.";
                            DB::rollBack();
                            return $response;
                        }
                        $assignment_question_learning_tree = DB::table('assignment_question_learning_tree')
                            ->where('assignment_question_id', $assignment_question->id)
                            ->first();
                        if ($assignment_question_learning_tree) {
                            $learning_tree_id = $assignment_question_learning_tree->learning_tree_id;
                        }
                    } else {
                        switch ($request->question_source) {
                            case('my_favorites'):
                                $assignment_question = DB::table('my_favorites')
                                    ->where('question_id', $question['question_id'])
                                    ->where('user_id', $request->user()->id)
                                    ->select('question_id', 'open_ended_submission_type', 'open_ended_text_editor', 'learning_tree_id')
                                    ->first();
                                $assignment_question_learning_tree = $assignment_question->learning_tree_id !== null;
                                $learning_tree_id = $assignment_question->learning_tree_id;
                                unset($assignment_question->learning_tree_id);
                                break;
                            case('my_questions'):
                            case('all_questions'):
                                $assignment_question = DB::table('questions')
                                    ->where('id', $question['question_id'])
                                    ->select('id AS question_id')
                                    ->first();
                                //they can always change the stuff below.  Since the question is not in an assignment I can't tell what the instructor wants
                                $assignment_question->open_ended_submission_type = 0;
                                $assignment_question->open_ended_text_editor = null;
                                $assignment_question_learning_tree = false;
                                break;
                            default:
                                $response['message'] = "$request->question_source is not a valid question source.";
                                return $response;

                        }

                    }

                    if ($assignment->file_upload_mode === 'compiled_pdf'
                        && !in_array($assignment_question->open_ended_submission_type, ['0', 'file'])) {
                        $response['message'] = "Your assignment is of file upload type Compiled PDF but you're trying to remix an open-ended type of $assignment_question->open_ended_submission_type.  If you would like to use this question, please edit your assignment and change the file upload type to 'Individual Assessment Upload' or 'Compiled Upload/Individual Assessment Upload'.";
                        DB::rollBack();
                        return $response;
                    }

                    unset($assignment_question->id);
                    $assignment_question->assignment_id = $assignment->id;
                    $assignment_question->order = count($assignment_questions) + $key + 1;
                    switch ($assignment->points_per_question) {
                        case('number of points'):
                            $assignment_question->points = $assignment->default_points_per_question;
                            break;
                        case('question weight'):
                            $assignment_question->points = 0;//will be updated below
                            $assignment_question->weight = 1;
                            break;
                        default:
                            throw new exception ("Invalid points_per_question");
                    }


                    $assignment_question->created_at = Carbon::now();
                    $assignment_question->updated_at = Carbon::now();
                    $assignment_question->completion_scoring_mode = ($assignment->scoring_type === 'c')
                        ? $assignment->default_completion_scoring_mode
                        : null;
                    $assignment_question_exists = DB::table('assignment_question')
                        ->where('assignment_id', $assignment->id)
                        ->where('question_id', $question['question_id'])->first();
                    $assignment_question_arr = (array)$assignment_question;

                    if ($assignment_question_exists) {
                        DB::table('assignment_question')
                            ->where('assignment_id', $assignment->id)
                            ->where('question_id', $question['question_id'])
                            ->update($assignment_question_arr);
                    } else {
                        DB::table('assignment_question')->insertGetId($assignment_question_arr);
                        if (!$assignment_question_learning_tree) {
                            $betaCourseApproval->updateBetaCourseApprovalsForQuestion($assignment, $question['question_id'], 'add');
                        }
                    }
                    if ($assignment_question_learning_tree) {
                        if (!DB::table('assignment_question_learning_tree')
                            ->where('assignment_question_id', $assignment_question->id)
                            ->first()) {
                            DB::table('assignment_question_learning_tree')
                                ->insert(['assignment_question_id' => $assignment_question->id,
                                    'learning_tree_id' => $learning_tree_id]);
                            $betaCourseApproval->updateBetaCourseApprovalsForQuestion($assignment, $question['question_id'], 'add', $learning_tree_id);
                        }
                    }
                }
            }
            //clean up the order, just in case
            $current_ordered_assignment_questions = DB::table('assignment_question')
                ->where('assignment_id', $assignment->id)
                ->orderBy('order')
                ->select('id')
                ->get();

            foreach ($current_ordered_assignment_questions as $key => $assignment_question) {
                DB::table('assignment_question')->where('id', $assignment_question->id)
                    ->update(['order' => $key + 1]);

            }
            $assignmentSyncQuestion->updatePointsBasedOnWeights($assignment);
            DB::commit();
            $response['message'] = "The assessment has been added to your assignment.";
            $response['type'] = 'success';
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating the questions for this assignment.  Please try again or contact us for assistance.";
        }

        return $response;
    }

    public
    function storeOpenEndedDefaultText(Request $request, Assignment $assignment, Question $question, AssignmentSyncQuestion $assignmentSyncQuestion)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('storeOpenEndedSubmissionDefaultText', [$assignmentSyncQuestion, $assignment, $question]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }


        try {
            DB::table('assignment_question')
                ->where('assignment_id', $assignment->id)
                ->where('question_id', $question->id)
                ->update(['open_ended_default_text' => $request->open_ended_default_text]);
            $response['message'] = 'The default text has been updated.';
            $response['type'] = 'success';

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error saving the default open ended text.  Please try again or contact us for assistance.";
        }
        return $response;


    }

    /**
     * @param Request $request
     * @param Assignment $assignment
     * @param AssignmentSyncQuestion $assignmentSyncQuestion
     * @return array
     * @throws Exception
     */
    public
    function order(Request $request, Assignment $assignment, AssignmentSyncQuestion $assignmentSyncQuestion)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('order', [$assignmentSyncQuestion, $assignment]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            DB::beginTransaction();
            $assignmentSyncQuestion->orderQuestions($request->ordered_questions, $assignment);
            DB::commit();
            $response['message'] = 'Your questions have been re-ordered.';
            $response['type'] = 'success';

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error ordering the questions for this assignment.  Please try again or contact us for assistance.";
        }
        return $response;


    }

    public
    function getClickerQuestion(Request $request, Assignment $assignment)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('getClickerQuestion', $assignment);

        if (!$authorized->allowed()) {

            $response['message'] = $authorized->message();
            return $response;
        }

        try {

            $clicker_question = DB::table('assignment_question')
                ->where('assignment_id', $assignment->id)
                ->whereNotNull('clicker_start')
                ->select('question_id')
                ->first();
            $response['question_id'] = $clicker_question ? $clicker_question->question_id : false;
            $response['type'] = 'success';

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error starting this clicker assessment.  Please try again or contact us for assistance.";
        }
        return $response;


    }

    public
    function startClickerAssessment(StartClickerAssessment $request, Assignment $assignment, Question $question, AssignmentSyncQuestion $assignmentSyncQuestion)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('startClickerAssessment', [$assignmentSyncQuestion, $assignment, $question]);
        $data = $request->validated();

        if (!$authorized->allowed()) {

            $response['message'] = $authorized->message();
            return $response;
        }

        try {

            $data = $request->validated();
            $clicker_start = CarbonImmutable::now();
            $seconds_padding = 6;
            $clicker_end = $clicker_start->add($data['time_to_submit'])->addSeconds($seconds_padding);
            DB::beginTransaction();
            DB::table('assignment_question')->where('assignment_id', $assignment->id)->update([
                'clicker_start' => null,
                'clicker_end' => null
            ]);

            //update individual due dates
            /*TODO: do this for individuals?
            if (strtotime($clicker_end) > strtotime($assignment->due)) {
                DB::table('assign_to_timings')->where('id', $assignment->id)
                    ->update(['due' => $clicker_end]);
            }*/

            DB::table('assignment_question')->where('assignment_id', $assignment->id)
                ->where('question_id', $question->id)
                ->update([
                    'clicker_start' => $clicker_start,
                    'clicker_end' => $clicker_end
                ]);
            DB::commit();
            $response['time_left'] = $clicker_end->subSeconds($seconds_padding)->diffInMilliseconds($clicker_start);
            $response['type'] = 'success';
            $response['message'] = 'Your students can begin submitting responses.';

        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error starting this clicker assessment.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    /**
     * @param Assignment $assignment
     * @return array
     * @throws Exception
     */
    public
    function getQuestionIdsByAssignment(Assignment $assignment)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('view', $assignment);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $response['type'] = 'success';
            $response['question_ids'] = json_encode($assignment->questions()->pluck('question_id'));//need to do since it's an array
            $response['question_ids_array'] = $assignment->questions()->pluck('question_id')->toArray();
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the assignment questions.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    /**
     * @param Assignment $assignment
     * @param Question $question
     * @return array
     * @throws Exception
     */

    public
    function getQuestionSummaryByAssignment(Assignment $assignment, Question $question)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('view', $assignment);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $dom = new DOMDocument();
            //Get all assignment questions Question Upload, Solution, Number of Points
            $assignment_questions = DB::table('assignment_question')
                ->join('questions', 'assignment_question.question_id', '=', 'questions.id')
                ->leftJoin('assignment_question_learning_tree', 'assignment_question.id', '=', 'assignment_question_learning_tree.assignment_question_id')
                ->leftJoin('learning_trees', 'assignment_question_learning_tree.learning_tree_id', '=', 'learning_trees.id')
                ->where('assignment_id', $assignment->id)
                ->orderBy('order')
                ->select('assignment_question.*',
                    'questions.library',
                    'questions.page_id',
                    'questions.technology_iframe',
                    'questions.technology',
                    'questions.title',
                    DB::raw('questions.id AS question_id'),
                    'questions.library',
                    'questions.qti_json',
                    'questions.question_editor_user_id',
                    'questions.answer_html',
                    'questions.solution_html',
                    'learning_tree_id',
                    'learning_trees.user_id AS learning_tree_user_id',
                    'learning_trees.description AS learning_tree_description')
                ->get();

            $question_ids = [];
            foreach ($assignment_questions as $key => $value) {
                $question_ids[] = $value->question_id;
            }

            $solutions = DB::table('solutions')
                ->whereIn('question_id', $question_ids)
                ->where('user_id', $assignment->course->user_id)
                ->get();

            $h5p_non_adapts = DB::table('questions')
                ->join('h5p_non_adapts', 'questions.h5p_type_id', '=', 'h5p_non_adapts.id')
                ->whereIn('questions.id', $question_ids)
                ->select('questions.id AS question_id', 'h5p_non_adapts.name')
                ->get();

            $h5p_non_adapts_by_question_id = [];
            foreach ($h5p_non_adapts as $h5p_non_adapt) {
                $h5p_non_adapts_by_question_id[$h5p_non_adapt->question_id] = $h5p_non_adapt->name;
            }

            if ($solutions) {
                foreach ($solutions as $key => $value) {
                    $assignment_solutions_by_question_id[$value->question_id]['original_filename'] = $value->original_filename;
                    $assignment_solutions_by_question_id[$value->question_id]['solution_text'] = $value->text;
                    $assignment_solutions_by_question_id[$value->question_id]['solution_type'] = $value->type;
                    $assignment_solutions_by_question_id[$value->question_id]['solution_file_url'] = Storage::disk('s3')->temporaryUrl("solutions/{$assignment->course->user_id}/{$value->file}", now()->addMinutes(360));

                }
            }
            $h5p_questions_exists = false;
            $rows = [];
            foreach ($assignment_questions as $value) {
                $columns = [];
                $columns['title'] = $value->title;
                if (!$value->title) {
                    $Libretext = new Libretext(['library' => $value->library]);
                    $title = $Libretext->getTitle($value->page_id);
                    Question::where('id', $value->question_id)->update(['title' => $title]);
                    $columns['title'] = $title;
                }
                if ($value->open_ended_submission_type === 'text') {
                    $value->open_ended_submission_type = $value->open_ended_text_editor . ' text';
                }

                $columns['submission'] = Helper::getSubmissionType($value);

                $columns['auto_graded_only'] = !($value->technology === 'text' || $value->open_ended_submission_type);
                $columns['is_open_ended'] = $value->open_ended_submission_type !== '0';
                $columns['is_auto_graded'] = $value->technology !== 'text';
                $columns['learning_tree'] = $value->learning_tree_id !== null;
                $columns['learning_tree_id'] = $value->learning_tree_id;
                $columns['learning_tree_user_id'] = $value->learning_tree_user_id;
                $columns['points'] = Helper::removeZerosAfterDecimal($value->points);
                $columns['solution'] = $assignment_solutions_by_question_id[$value->question_id]['original_filename'] ?? false;

                $columns['h5p_non_adapt'] = $h5p_non_adapts_by_question_id[$value->question_id] ?? null;
                $columns['solution_file_url'] = $assignment_solutions_by_question_id[$value->question_id]['solution_file_url'] ?? false;
                $columns['solution_text'] = $assignment_solutions_by_question_id[$value->question_id]['solution_text'] ?? false;
                $columns['solution_type'] = null;


                $columns['solution_html'] = $question->addTimeToS3Images($value->solution_html, $dom);
                if (!$columns['solution_html']) {
                    $columns['solution_html'] = $value->answer_html;
                }
                if ($columns['solution_html']) {
                    $columns['solution_type'] = 'html';
                }
                if ($columns['solution_file_url']) {
                    $columns['solution_type'] = 'q';
                }
                $columns['qti_answer_json'] = '';
                if (!$columns['solution_html'] && $value->qti_json) {
                    $columns['qti_answer_json'] = $question->formatQtiJson($value->qti_json, [], true);
                }
                $columns['order'] = $value->order;
                $columns['question_id'] = $columns['id'] = $value->question_id;
                $columns['technology'] = $value->technology;
                if ($value->technology === 'h5p') {
                    $h5p_questions_exists = true;
                }
                $columns['assignment_id_question_id'] = "{$assignment->id}-{$value->question_id}";
                $columns['library'] = $value->library;
                $columns['question_editor_user_id'] = $value->question_editor_user_id;
                $columns['mindtouch_url'] = "https://{$value->library}.libretexts.org/@go/page/{$value->page_id}";
                $rows[] = $columns;
            }
            $response['assessment_type'] = $assignment->assessment_type;
            $response['beta_assignments_exist'] = $assignment->betaAssignments() !== [];
            $response['is_beta_assignment'] = $assignment->isBetaAssignment();
            $response['is_alpha_course'] = $assignment->course->alpha === 1;
            $response['submissions_exist'] = $assignment->submissions->isNotEmpty() || $assignment->fileSubmissions->isNotEmpty();
            $response['is_question_weight'] = $assignment->points_per_question === 'question weight';
            $response['course_has_anonymous_users'] = $assignment->course->anonymous_users === 1;
            $response['solutions_availability'] = $assignment->solutions_availability;
            $response['h5p_questions_exist'] = $h5p_questions_exists;
            $response['real_time_with_multiple_attempts'] = $assignment->assessment_type === 'real time' && $assignment->number_of_allowed_attempts !== '1';
            $response['type'] = 'success';
            $response['rows'] = $rows;


        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the questions summary for this assignment.  Please try again or contact us for assistance.";
        }
        return $response;

    }


    private
    function _getSolutionLink($assignment, $assignment_solutions_by_question_id, $question_id)
    {
        return isset($assignment_solutions_by_question_id[$question_id]) ?
            '<a href="' . Storage::disk('s3')->temporaryUrl("solutions/{$assignment->course->user_id}/{$assignment_solutions_by_question_id[$question_id]['file']}", now()->addMinutes(360)) . '" target="_blank">' . $assignment_solutions_by_question_id[$question_id]['original_filename'] . '</a>'
            : 'None';
    }

    public
    function getQuestionInfoByAssignment(Assignment $assignment)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('view', $assignment);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $response['questions'] = [];
            $response['question_files'] = [];
            $response['question_ids'] = [];
            $response['clicker_status'] = [];
            $assignment_question_info = DB::table('assignment_question')
                ->where('assignment_id', $assignment->id)
                ->orderBy('order')
                ->get();
            if ($assignment_question_info->isNotEmpty()) {
                foreach ($assignment_question_info as $question_info) {
                    //for getQuestionsByAssignment (internal)
                    $question_info->points = Helper::removeZerosAfterDecimal($question_info->points);

                    $response['questions'][$question_info->question_id] = $question_info;
                    //for the axios call from questions.get.vue
                    $response['question_ids'][] = $question_info->question_id;
                    if ($question_info->open_ended_submission_type === 'file') {
                        $response['question_files'][] = $question_info->question_id;
                    }

                }
            }
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the assignment questions.  Please try again or contact us for assistance.";
        }
        return $response;


    }

    public
    function updateOpenEndedSubmissionType(UpdateOpenEndedSubmissionType $request,
                                           Assignment                    $assignment,
                                           Question                      $question,
                                           AssignmentSyncQuestion        $assignmentSyncQuestion,
                                           SubmissionFile                $submissionFile): array
    {

        $response['type'] = 'error';

        $authorized = Gate::inspect('updateOpenEndedSubmissionType', [$assignmentSyncQuestion, $assignment, $question, $submissionFile]);

        if (!$authorized->allowed()) {

            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $assignment_ids = [$assignment->id];
            if ($assignment->course->alpha) {
                $assignment_ids = $assignment->addBetaAssignmentIds();
            }

            $data = $request->validated();
            $open_ended_text_editor = null;
            if ((strpos($data['open_ended_submission_type'], 'text') !== false)) {
                $open_ended_text_editor = str_replace(' text', '', $data['open_ended_submission_type']);
                $data['open_ended_submission_type'] = 'text';

            }
            DB::table('assignment_question')
                ->whereIn('assignment_id', $assignment_ids)
                ->where('question_id', $question->id)
                ->update(['open_ended_submission_type' => $data['open_ended_submission_type'],
                    'open_ended_text_editor' => $open_ended_text_editor]);
            $response['type'] = 'success';
            $response['message'] = "The open-ended submission type has been updated.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating the open-ended submission type.  Please try again or contact us for assistance.";
        }
        return $response;
    }


    /**
     * @param UpdateCompletionScoringModeRequest $request
     * @param Assignment $assignment
     * @param Question $question
     * @param AssignmentSyncQuestion $assignmentSyncQuestion
     * @return array
     * @throws Exception
     */
    public
    function updateCompletionScoringMode(UpdateCompletionScoringModeRequest $request,
                                         Assignment                         $assignment,
                                         Question                           $question,
                                         AssignmentSyncQuestion             $assignmentSyncQuestion): array
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('update', [$assignmentSyncQuestion, $assignment]);
        $data = $request->validated();

        if (!$authorized->allowed()) {

            $response['message'] = $authorized->message();
            return $response;
        }

        if ($assignment->scoring_type !== 'c') {
            $response['message'] = "This option is only available for assignments that are graded for 'completion'.";
        }

        $completion_scoring_mode = Helper::getCompletionScoringMode('c', $data['completion_scoring_mode'], $request->completion_split_auto_graded_percentage);
        $assignment_ids = [$assignment->id];
        if ($assignment->course->alpha) {
            $assignment_ids = $assignment->addBetaAssignmentIds();
        }
        try {
            $is_randomized_assignment = $assignment->number_of_randomized_assessments;
            if ($is_randomized_assignment) {
                DB::table('assignment_question')
                    ->whereIn('assignment_id', $assignment_ids)
                    ->update(['points' => $data['points']]);
                $assignment->default_completion_scoring_mode = $completion_scoring_mode;
                $assignment->save();
                $message = 'Since this is a randomized assignment, all questions now have the same completion scoring mode.';
            } else {
                DB::table('assignment_question')
                    ->whereIn('assignment_id', $assignment_ids)
                    ->where('question_id', $question->id)
                    ->update(['completion_scoring_mode' => $completion_scoring_mode]);
                $message = 'The completion scoring mode has been updated.';
            }
            $response['type'] = 'success';
            $response['message'] = $message;
            $response['completion_scoring_mode'] = $completion_scoring_mode;
            $response['update_completion_scoring_mode'] = $is_randomized_assignment;

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating the number of points.  Please try again or contact us for assistance.";
        }
        return $response;


    }

    /**
     * @param UpdateAssignmentQuestionPointsRequest $request
     * @param Assignment $assignment
     * @param Question $question
     * @param AssignmentSyncQuestion $assignmentSyncQuestion
     * @return array
     * @throws Exception
     */
    public
    function updatePoints(UpdateAssignmentQuestionPointsRequest $request, Assignment $assignment, Question $question, AssignmentSyncQuestion $assignmentSyncQuestion): array
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('update', [$assignmentSyncQuestion, $assignment]);
        $data = $request->validated();

        if (!$authorized->allowed()) {

            $response['message'] = $authorized->message();
            return $response;
        }


        $assignment_ids = [$assignment->id];
        if ($assignment->course->alpha) {
            $assignment_ids = $assignment->addBetaAssignmentIds();
        }
        try {
            $is_randomized_assignment = $assignment->number_of_randomized_assessments;
            if ($is_randomized_assignment) {
                DB::table('assignment_question')
                    ->whereIn('assignment_id', $assignment_ids)
                    ->update(['points' => $data['points']]);
                $assignment->default_points_per_question = $data['points'];
                $assignment->save();
                $message = 'Since this is a randomized assignment, all question points have been updated to the same value.';
            } else {
                DB::table('assignment_question')
                    ->whereIn('assignment_id', $assignment_ids)
                    ->where('question_id', $question->id)
                    ->update(['points' => $data['points']]);
                $message = 'The number of points have been updated.';

            }
            $response['type'] = 'success';
            $response['message'] = $message;
            $response['update_points'] = $is_randomized_assignment;

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating the number of points.  Please try again or contact us for assistance.";
        }
        return $response;


    }

    /**
     * @param UpdateAssignmentQuestionWeightRequest $request
     * @param Assignment $assignment
     * @param Question $question
     * @param AssignmentSyncQuestion $assignmentSyncQuestion
     * @return array
     * @throws Exception
     */
    public function updateWeight(UpdateAssignmentQuestionWeightRequest $request,
                                 Assignment                            $assignment,
                                 Question                              $question,
                                 AssignmentSyncQuestion                $assignmentSyncQuestion)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('update', [$assignmentSyncQuestion, $assignment]);
        $data = $request->validated();

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        $is_randomized_assignment = $assignment->number_of_randomized_assessments;
        if ($is_randomized_assignment) {
            $response['message'] = "Weights for randomized assignments cannot be altered.";
            return $response;
        }


        $assignment_ids = [$assignment->id];
        if ($assignment->course->alpha) {
            $assignment_ids = $assignment->addBetaAssignmentIds();
        }
        try {
            DB::beginTransaction();
            DB::table('assignment_question')
                ->whereIn('assignment_id', $assignment_ids)
                ->where('question_id', $question->id)
                ->update(['weight' => $data['weight']]);
            foreach ($assignment_ids as $assignment_id) {
                $assignment_to_update = Assignment::find($assignment_id);
                $assignmentSyncQuestion->updatePointsBasedOnWeights($assignment_to_update);
            }

            $message = "The weight has been updated and the questions' points for the entire assignment have been updated.";
            $response['type'] = 'success';
            $response['message'] = $message;
            $response['updated_points'] = $assignmentSyncQuestion->getQuestionPointsByAssignment($assignment);
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating the number of points.  Please try again or contact us for assistance.";
        }
        return $response;


    }

    /**
     * @param Assignment $assignment
     * @param Question $question
     * @param AssignmentSyncQuestion $assignmentSyncQuestion
     * @param BetaCourseApproval $betaCourseApproval
     * @return array
     * @throws Exception
     */
    public
    function store(Assignment             $assignment,
                   Question               $question,
                   AssignmentSyncQuestion $assignmentSyncQuestion,
                   BetaCourseApproval     $betaCourseApproval): array
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('add', [$assignmentSyncQuestion, $assignment]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        if ($assignment->cannotAddOrRemoveQuestionsForQuestionWeightAssignment()) {
            $response['message'] = "You cannot add a question since there are already submissions and this assignment computes points using question weights.";
            return $response;
        }
        try {
            DB::beginTransaction();
            $assignmentSyncQuestion->store($assignment, $question, $betaCourseApproval);
            $response['message'] = 'The question has been added to the assignment.';
            $response['type'] = 'success';
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error adding the question to the assignment.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    /**
     * @param Assignment $assignment
     * @param Question $question
     * @param AssignmentSyncQuestion $assignmentSyncQuestion
     * @param BetaCourseApproval $betaCourseApproval
     * @return array
     * @throws Exception
     */
    public
    function destroy(Assignment             $assignment,
                     Question               $question,
                     AssignmentSyncQuestion $assignmentSyncQuestion,
                     BetaCourseApproval     $betaCourseApproval): array
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('delete', [$assignmentSyncQuestion, $assignment, $question]);


        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        if ($assignment->cannotAddOrRemoveQuestionsForQuestionWeightAssignment()) {
            $response['message'] = "You cannot remove this question since there are already submissions and this assignment computes points using question weights.";
            return $response;
        }

        try {
            DB::beginTransaction();

            if ($assignment->number_of_randomized_assessments) {

                $assignment_questions = DB::table('assignment_question')
                    ->where('assignment_id', $assignment->id)
                    ->get();
                if ($assignment_questions->count() === $assignment->number_of_randomized_assessments + 1) {
                    $response['message'] = "You can't remove a question because there wouldn't be enough questions left to randomize from.";
                    return $response;
                }
                $question_ids = $assignment->questions->pluck('id')->toArray();
                $randomized_assignment_questions = RandomizedAssignmentQuestion::where('assignment_id', $assignment->id)->get();
                $randomized_assignment_questions_by_user_id = [];
                foreach ($randomized_assignment_questions as $randomized_assignment_question) {
                    if (!isset($randomized_assignment_questions_by_user_id[$randomized_assignment_question->user_id])) {
                        $randomized_assignment_questions_by_user_id[$randomized_assignment_question->user_id] = [];
                    }
                    $randomized_assignment_questions_by_user_id[$randomized_assignment_question->user_id][] = $randomized_assignment_question->question_id;
                }

                foreach ($randomized_assignment_questions_by_user_id as $user_id => $user_question_ids) {
                    if (in_array($question->id, $user_question_ids)) {
                        $other_question_id = $this->getOtherRandomizedQuestionId($user_question_ids, $question_ids, $question->id);
                        if (!$other_question_id) {
                            $response['message'] = "We were unable to remove the question due to an issue with re-configuring the randomizations.  Please contact support.";
                            return $response;
                        }
                        $randomizedAssignmentQuestion = new RandomizedAssignmentQuestion();
                        $randomizedAssignmentQuestion->assignment_id = $assignment->id;
                        $randomizedAssignmentQuestion->question_id = $other_question_id;
                        $randomizedAssignmentQuestion->user_id = $user_id;
                        $randomizedAssignmentQuestion->save();
                    }
                    ///get all assignment questions
                    /// get all students for which this affects
                    /// add a different question


                    $response['message'] = "As this is a randomized assignment, please ask your students to revisit their assignment as a question may have been updated.";
                }
            }

            $assignmentSyncQuestion->updateAssignmentScoreBasedOnRemovedQuestion($assignment, $question);
            $assignment_question_id = DB::table('assignment_question')->where('question_id', $question->id)
                ->where('assignment_id', $assignment->id)
                ->first()
                ->id;

            DB::table('submissions')->where('assignment_id', $assignment->id)
                ->where('question_id', $question->id)
                ->delete();
            DB::table('submission_files')->where('assignment_id', $assignment->id)
                ->where('question_id', $question->id)
                ->delete();
            $assignment_question_learning_tree = DB::table('assignment_question_learning_tree')
                ->where('assignment_question_id', $assignment_question_id)
                ->first();
            $learning_tree_id = $assignment_question_learning_tree ? $assignment_question_learning_tree->learning_tree_id : 0;//needed for the course approvals piece
            if ($learning_tree_id) {
                $learning_tree_tables = ['learning_tree_successful_branches', 'learning_tree_time_lefts', 'remediation_submissions'];
                foreach ($learning_tree_tables as $learning_tree_table) {
                    DB::table($learning_tree_table)
                        ->where('assignment_id', $assignment->id)
                        ->where('learning_tree_id', $learning_tree_id)
                        ->delete();
                }
            }
            DB::table('assignment_question_learning_tree')
                ->where('assignment_question_id', $assignment_question_id)
                ->delete();
            DB::table('assignment_question')->where('question_id', $question->id)
                ->where('assignment_id', $assignment->id)
                ->delete();
            DB::table('randomized_assignment_questions')->where('assignment_id', $assignment->id)
                ->where('question_id', $question->id)
                ->delete();
            DB::table('question_level_overrides')->where('question_id', $question->id)
                ->where('assignment_id', $assignment->id)
                ->delete();
            $currently_ordered_questions = DB::table('assignment_question')
                ->where('assignment_id', $assignment->id)
                ->orderBy('order')
                ->get();

            if ($currently_ordered_questions) {
                $currently_ordered_question_ids = [];
                foreach ($currently_ordered_questions as $currently_ordered_question) {
                    $currently_ordered_question_ids[] = $currently_ordered_question->question_id;
                }
                $assignmentSyncQuestion->orderQuestions($currently_ordered_question_ids, $assignment);

                $assignmentSyncQuestion->updatePointsBasedOnWeights($assignment);
                if ($assignment->points_per_question === 'question weight') {
                    $response['updated_points'] = $assignmentSyncQuestion->getQuestionPointsByAssignment($assignment);
                }

            }


            $betaCourseApproval->updateBetaCourseApprovalsForQuestion($assignment, $question->id, 'remove', $learning_tree_id);

            DB::commit();
            $response['type'] = 'info';
            $response['message'] = 'The question has been removed from the assignment.';
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error removing the question from the assignment.  Please try again or contact us for assistance.";
        }

        return $response;

    }


    /**
     * @param Assignment $assignment
     * @param Question $question
     * @param Submission $Submission
     * @param Extension $Extension
     * @param AssignmentSyncQuestion $assignmentSyncQuestion
     * @return array
     * @throws Exception
     */
    public
    function updateLastSubmittedAndLastResponse(Assignment             $assignment,
                                                Question               $question,
                                                Submission             $Submission,
                                                Extension              $Extension,
                                                AssignmentSyncQuestion $assignmentSyncQuestion)
    {
        /**helper function to get the response info from server side technologies...*/

        $submission = DB::table('submissions')
            ->where('question_id', $question->id)
            ->where('assignment_id', $assignment->id)
            ->where('user_id', Auth::user()->id)
            ->first();

        $gave_up = DB::table('can_give_ups')
            ->where('question_id', $question->id)
            ->where('assignment_id', $assignment->id)
            ->where('user_id', Auth::user()->id)
            ->where('status', 'gave up')
            ->first();


        $submissions_by_question_id[$question->id] = $submission;
        $question_technologies[$question->id] = Question::find($question->id)->technology;
        $response_info = $this->getResponseInfo($assignment, $Extension, $Submission, $submissions_by_question_id, $question_technologies, $question->id);
        $solution = false;
        $solution_type = false;
        $solution_file_url = false;
        $solution_text = false;

        $qti_answer_json = null;
        $real_time_show_solution = $this->showRealTimeSolution($assignment, $Submission, $submissions_by_question_id[$question->id], $question);
        if ($real_time_show_solution || $gave_up) {
            $solution_info = DB::table('solutions')
                ->where('question_id', $question->id)
                ->where('user_id', $assignment->course->user_id)
                ->first();
            if ($solution_info) {
                $solution = $solution_info->original_filename;
                $solution_type = $solution_info->type;
                $solution_file_url = $solution_info->file;
                $solution_text = $solution_info->text;
            }
            $qti_answer_json = $question->qti_json;
            if (($question->solution_html || $question->answer_html) && !$solution) {
                $solution_type = 'html';
            }
        }

        $last_submitted = $response_info['last_submitted'] === 'N/A'
            ? 'N/A'
            : $this->convertUTCMysqlFormattedDateToHumanReadableLocalDateAndTime($response_info['last_submitted'],
                Auth::user()->time_zone, 'M d, Y g:i:s a');

        return ['last_submitted' => $last_submitted,
            'student_response' => $response_info['student_response'],
            'submission_count' => $response_info['submission_count'],
            'submission_score' => Helper::removeZerosAfterDecimal($response_info['submission_score']),
            'late_penalty_percent' => $response_info['late_penalty_percent'],
            'late_question_submission' => $response_info['late_question_submission'],
            'answered_correctly_at_least_once' => $response_info['answered_correctly_at_least_once'],
            'qti_answer_json' => $qti_answer_json,
            'solution' => $solution,
            'solution_file_url' => $solution_file_url,
            'solution_text' => $solution_text,
            'solution_type' => $solution_type,
            'answer_html' => $question->answer_html,
            'solution_html' => $question->solution_html,
            'completed_all_assignment_questions' => $assignmentSyncQuestion->completedAllAssignmentQuestions($assignment)
        ];

    }

    /**
     * @param Assignment $assignment
     * @param $Extension
     * @param Submission $Submission
     * @param $submissions_by_question_id
     * @param $question_technologies
     * @param $question_id
     * @return array
     * @throws Exception
     */
    public
    function getResponseInfo(Assignment $assignment,
                                        $Extension,
                             Submission $Submission,
                                        $submissions_by_question_id,
                                        $question_technologies,
                                        $question_id)
    {
        //$Extension will be the model when returning the information to the user at the individual level
        //it will be the actual date when doing it for the assignment since I just need to do it once
        $student_response = $question_technologies[$question_id] === 'qti' ? '' : 'N/A';
        $correct_response = null;
        $late_penalty_percent = 0;
        $submission_score = 0;
        $last_submitted = 'N/A';
        $submission_count = 0;
        $late_question_submission = false;
        $answered_correctly_at_least_once = 0;
        $reset_count = 0;

        if (isset($submissions_by_question_id[$question_id])) {
            $submission = $submissions_by_question_id[$question_id];
            $last_submitted = $submission->updated_at;
            $submission_score = $submission->score;
            $submission_count = $submission->submission_count;
            $reset_count = $submission->reset_count;
            $late_penalty_percent = $Submission->latePenaltyPercent($assignment, Carbon::parse($last_submitted));
            $late_question_submission = $this->isLateSubmission($Extension, $assignment, Carbon::parse($last_submitted));
            $answered_correctly_at_least_once = $submission->answered_correctly_at_least_once;

            $student_response = $Submission->getStudentResponse($submission, $question_technologies[$question_id]);

        }
        return compact('student_response',
            'correct_response',
            'submission_score',
            'last_submitted',
            'submission_count',
            'late_penalty_percent',
            'reset_count',
            'late_question_submission',
            'answered_correctly_at_least_once');

    }

    /**
     * @param Request $request
     * @param Assignment $assignment
     * @param Submission $Submission
     * @param SubmissionFile $SubmissionFile
     * @param Extension $Extension
     * @param AssignmentSyncQuestion $assignmentSyncQuestion
     * @param Enrollment $enrollment
     * @return array
     * @throws Exception
     */
    public
    function getQuestionsToView(Request                $request,
                                Assignment             $assignment,
                                Submission             $Submission,
                                SubmissionFile         $SubmissionFile,
                                Extension              $Extension,
                                AssignmentSyncQuestion $assignmentSyncQuestion,
                                Enrollment             $enrollment)
    {

        $response['type'] = 'error';
        $response['is_instructor_logged_in_as_student'] = session()->get('instructor_user_id') && request()->user()->role === 3;
        $response['is_instructor_with_anonymous_view'] = Helper::hasAnonymousUserSession()
            && request()->user()->role === 2
            && $assignment->course->user_id !== request()->user()->id;
        $authorized = Gate::inspect('view', $assignment);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {

            $enrollment = $enrollment->where('course_id', $assignment->course->id)
                ->where('user_id', $request->user()->id)
                ->first();
            $a11y = ($enrollment && $enrollment->a11y) || ($request->user()->role === 2);

            //determine "true" due date to see if submissions were late
            $extension = $Extension->getAssignmentExtensionByUser($assignment, Auth::user());
            $due_date_considering_extension = $assignment->assignToTimingByUser('due');

            if ($extension) {
                if (Carbon::parse($extension) > Carbon::parse($assignment->assignToTimingByUser('due'))) {
                    $due_date_considering_extension = $extension;
                }
            }


            $assignment_question_info = $this->getQuestionInfoByAssignment($assignment);

            $question_ids = [];
            $points = [];
            $weights = [];
            $solutions_by_question_id = [];
            if (!$assignment_question_info['questions']) {
                $response['type'] = 'success';
                $response['questions'] = [];
                return $response;
            }


            $user_as_collection = collect([Auth::user()]);
            // $submission_texts_by_question_and_user = $SubmissionText->getUserAndQuestionTextInfo($assignment, 'allStudents', $user_as_collection);


            $submission_files_by_question_and_user = $SubmissionFile->getUserAndQuestionFileInfo($assignment, 'allStudents', $user_as_collection);
            $submission_files = [];

            //want to just pull out the single user which will be returned for each question
            foreach ($submission_files_by_question_and_user as $key => $submission) {
                $submission_files[] = $submission[0];

            }

            $submission_files_by_question_id = [];
            foreach ($submission_files as $submission_file) {
                $submission_files_by_question_id[$submission_file['question_id']] = $submission_file;
            }

            $learning_trees_by_question_id = [];
            $learning_tree_penalties_by_question_id = [];
            $submitted_but_did_not_explore_learning_tree = [];
            $learning_tree_success_criteria_satisfied = [];
            $open_ended_submission_types = [];
            $open_ended_text_editors = [];
            $open_ended_default_texts = [];
            $completion_scoring_modes = [];
            $clicker_status = [];
            $clicker_time_left = [];
            $learning_tree_ids_by_question_id = [];
            $number_of_resets_by_question_id = [];
            $iframe_showns = [];


            foreach ($assignment_question_info['questions'] as $question) {
                $question_ids[$question->question_id] = $question->question_id;
                $open_ended_submission_types[$question->question_id] = $question->open_ended_submission_type;
                $open_ended_text_editors[$question->question_id] = $question->open_ended_text_editor;
                $open_ended_default_texts[$question->question_id] = $question->open_ended_default_text;
                $completion_scoring_modes[$question->question_id] = $question->completion_scoring_mode;
                $iframe_showns[$question->question_id] = ['attribution_information_shown_in_iframe' => (boolean)$question->attribution_information_shown_in_iframe,
                    'submission_information_shown_in_iframe' => (boolean)$question->submission_information_shown_in_iframe,
                    'assignment_information_shown_in_iframe' => (boolean)$question->assignment_information_shown_in_iframe];

                $points[$question->question_id] = Helper::removeZerosAfterDecimal($question->points);
                $weights[$question->question_id] = Helper::removeZerosAfterDecimal($question->weight);
                $solutions_by_question_id[$question->question_id] = false;//assume they don't exist
                $clicker_status[$question->question_id] = $assignmentSyncQuestion->getFormattedClickerStatus($question);
                if (!$question->clicker_start) {
                    $clicker_time_left[$question->question_id] = 0;
                } else {
                    $start = Carbon::now();
                    $end = Carbon::parse($question->clicker_end);
                    $num_seconds = 0;
                    if ($end > $start) {
                        $num_seconds = $end->diffInMilliseconds($start);
                    }
                    $clicker_time_left[$question->question_id] = $num_seconds;
                }

            }

            $question_info = DB::table('questions')
                ->select('questions.*', 'h5p_non_adapts.name AS h5p_non_adapt')
                ->leftJoin('h5p_non_adapts', 'questions.h5p_type_id', '=', 'h5p_non_adapts.id')
                ->whereIn('questions.id', $question_ids)
                ->get();

            $question_technologies = [];
            $question_h5p_non_adapt = [];
            foreach ($question_info as $question) {
                $question_technologies[$question->id] = $question->technology;
                $question_h5p_non_adapt[$question->id] = $question->h5p_non_adapt;
            }

            //these question_ids come from the assignment
            //in case an instructor accidentally assigns the same problem twice I added in assignment_id
            $submissions = DB::table('submissions')
                ->whereIn('question_id', $question_ids)
                ->where('user_id', Auth::user()->id)
                ->where('assignment_id', $assignment->id)
                ->get();

            $at_least_one_submission = DB::table('submissions')
                ->join('users', 'submissions.user_id', '=', 'users.id')
                ->where('assignment_id', $assignment->id)
                ->where('users.fake_student', 0)
                ->select('question_id')
                ->groupBy('question_id')
                ->get();

            $at_least_one_submission_file = DB::table('submission_files')
                ->join('users', 'submission_files.user_id', '=', 'users.id')
                ->where('assignment_id', $assignment->id)
                ->where('users.fake_student', 0)
                ->select('question_id')
                ->groupBy('question_id')
                ->get();

            $can_give_ups = DB::table('can_give_ups')
                ->where('assignment_id', $assignment->id)
                ->where('user_id', auth()->user()->id)
                ->where('status', 'can give up')
                ->select('question_id')
                ->get()
                ->pluck('question_id')
                ->toArray();
            $gave_ups = DB::table('can_give_ups')
                ->where('assignment_id', $assignment->id)
                ->where('user_id', auth()->user()->id)
                ->where('status', 'gave up')
                ->select('question_id')
                ->get()
                ->pluck('question_id')
                ->toArray();

            $questions_with_at_least_one_submission = [];
            foreach ($at_least_one_submission as $question) {
                $questions_with_at_least_one_submission[] = $question->question_id;
            }
            foreach ($at_least_one_submission_file as $question) {
                $questions_with_at_least_one_submission[] = $question->question_id;
            }


            $submissions_by_question_id = [];
            if ($submissions) {
                foreach ($submissions as $key => $value) {
                    $submissions_by_question_id[$value->question_id] = $value;
                }
            }

            //if they've already explored the learning tree, then we can let them view it right at the start
            if ($assignment->assessment_type === 'learning tree') {
                $number_of_resets_by_question_id = $assignment->getNumberOfResetsByQuestionId();
                foreach ($assignment->learningTrees() as $value) {
                    $learning_tree_ids_by_question_id[$value->question_id] = $value->learning_tree_id;
                    $submission_exists_by_question_id = isset($submissions_by_question_id[$value->question_id]);
                    $learning_trees_by_question_id[$value->question_id] =
                        $submission_exists_by_question_id
                            ? json_decode($value->learning_tree)->blocks
                            : null;
                    $learning_tree_penalties_by_question_id[$value->question_id] = $submission_exists_by_question_id
                        ? min((($submissions_by_question_id[$value->question_id]->submission_count - 1) * $assignment->submission_count_percent_decrease), 100) . '%'
                        : '0%';
                    $submitted_but_did_not_explore_learning_tree[$value->question_id] = $submission_exists_by_question_id && ($submissions_by_question_id[$value->question_id]->learning_tree_success_criteria_satisfied === null);
                    $learning_tree_success_criteria_satisfied[$value->question_id] = $submission_exists_by_question_id && $submissions_by_question_id[$value->question_id]->learning_tree_success_criteria_satisfied !== null;
                }
            }


            $mean_and_std_dev_by_question_submissions = $this->getMeanAndStdDevByColumn('submissions', 'assignment_id', [$assignment->id], 'question_id');
            $mean_and_std_dev_by_submission_files = $this->getMeanAndStdDevByColumn('submission_files', 'assignment_id', [$assignment->id], 'question_id');


            $seeds = DB::table('seeds')
                ->whereIn('question_id', $question_ids)
                ->where('user_id', Auth::user()->id)
                ->where('assignment_id', $assignment->id)
                ->get();

            $seeds_by_question_id = [];
            if ($seeds) {
                foreach ($seeds as $key => $value) {
                    $seeds_by_question_id[$value->question_id] = $value->seed;
                }
            }
            $questions_for_which_seeds_exist = array_keys($seeds_by_question_id);


            $solutions = DB::table('solutions')
                ->whereIn('question_id', $question_ids)
                ->where('user_id', $assignment->course->user_id)
                ->get();

            if ($solutions) {
                foreach ($solutions as $key => $value) {
                    $solutions_by_question_id[$value->question_id]['original_filename'] = $value->original_filename;
                    $solutions_by_question_id[$value->question_id]['solution_text'] = $value->text;
                    $solutions_by_question_id[$value->question_id]['solution_type'] = $value->type;
                    $solutions_by_question_id[$value->question_id]['solution_file_url'] = Storage::disk('s3')->temporaryUrl("solutions/{$assignment->course->user_id}/{$value->file}", now()->addMinutes(360));

                }
            }


            $domd = new DOMDocument();
            $JWE = new JWE();

            $randomly_chosen_questions = [];
            if ($assignment->number_of_randomized_assessments && $request->user()->role == 3) {
                $randomly_chosen_questions = $this->getRandomlyChosenQuestions($assignment, $request->user());
            }

            $shown_hints = DB::table('shown_hints')
                ->where('assignment_id', $assignment->id)
                ->where('user_id', Auth::user()->id)
                ->get('question_id')
                ->pluck('question_id')
                ->toArray();
            foreach ($assignment->questions as $key => $question) {
                if ($assignment->number_of_randomized_assessments
                    && $request->user()->role == 3
                    && !$request->user()->fake_student
                    && !in_array($question->id, $randomly_chosen_questions)) {
                    $assignment->questions->forget($key);
                    continue;
                }
                $iframe_technology = true;//assume there's a technology --- will be set to false once there isn't
                $technology_src = '';
                $assignment->questions[$key]['loaded_question_updated_at'] = $question->updated_at->timestamp;
                $assignment->questions[$key]['library'] = $question->library;
                $assignment->questions[$key]['h5p_non_adapt'] = $question_h5p_non_adapt[$question->id];
                $assignment->questions[$key]['page_id'] = $question->page_id;
                $assignment->questions[$key]['title'] = $question->title;
                $assignment->questions[$key]['author'] = $question->author;
                $assignment->questions[$key]['has_at_least_one_submission'] = in_array($question->id, $questions_with_at_least_one_submission);
                $assignment->questions[$key]['private_description'] = $request->user()->role === 2
                    ? $question->private_description
                    : '';
                $assignment->questions[$key]['license'] = $question->license;
                $assignment->questions[$key]['attribution'] = $question->attribution;
                $assignment->questions[$key]['assignment_information_shown_in_iframe'] = $iframe_showns[$question->id]['assignment_information_shown_in_iframe'];
                $assignment->questions[$key]['submission_information_shown_in_iframe'] = $iframe_showns[$question->id]['submission_information_shown_in_iframe'];
                $assignment->questions[$key]['attribution_information_shown_in_iframe'] = $iframe_showns[$question->id]['attribution_information_shown_in_iframe'];
                $assignment->questions[$key]['clicker_status'] = $clicker_status[$question->id];
                $assignment->questions[$key]['clicker_time_left'] = $clicker_time_left[$question->id];
                $assignment->questions[$key]['points'] = Helper::removeZerosAfterDecimal(round($points[$question->id], 4));
                $assignment->questions[$key]['weight'] = $weights[$question->id];
                $assignment->questions[$key]['mindtouch_url'] = $request->user()->role === 3
                    ? ''
                    : "https://{$question->library}.libretexts.org/@go/page/{$question->page_id}";

                $response_info = $this->getResponseInfo($assignment, $extension, $Submission, $submissions_by_question_id, $question_technologies, $question->id);

                $student_response = $response_info['student_response'];
                $correct_response = $response_info['correct_response'];
                $answered_correctly_at_least_once = $response_info['answered_correctly_at_least_once'];
                $submission_score = Helper::removeZerosAfterDecimal($response_info['submission_score']);
                $last_submitted = $response_info['last_submitted'];
                $submission_count = $response_info['submission_count'];
                $late_question_submission = $response_info['late_question_submission'];
                $reset_count = $response_info['reset_count'];

                $assignment->questions[$key]['student_response'] = $student_response;
                $assignment->questions[$key]['open_ended_submission_type'] = $open_ended_submission_types[$question->id];
                $assignment->questions[$key]['open_ended_text_editor'] = $open_ended_text_editors[$question->id];
                $assignment->questions[$key]['open_ended_default_text'] = $open_ended_default_texts[$question->id];
                $assignment->questions[$key]['completion_scoring_mode'] = $completion_scoring_modes[$question->id];

                $real_time_show_solution = isset($submissions_by_question_id[$question->id]) && $this->showRealTimeSolution($assignment, $Submission, $submissions_by_question_id[$question->id], $question);
                $can_give_up = in_array($question->id, $can_give_ups);
                $gave_up = in_array($question->id, $gave_ups);
                $show_solution = (!Helper::isAnonymousUser() || !Helper::hasAnonymousUserSession())
                    &&
                    ($assignment->solutions_released || $real_time_show_solution || $gave_up);

                if ($show_solution) {
                    $assignment->questions[$key]['correct_response'] = $correct_response;
                }

                $assignment->questions[$key]['can_give_up'] = $can_give_up;
                $assignment->questions[$key]['solution_exists'] = $solutions_by_question_id[$question->id]
                    || $assignment->questions[$key]->answer_html
                    || $assignment->questions[$key]->solution_html;

                if ($assignment->show_scores) {
                    $assignment->questions[$key]['submission_score'] = $submission_score;
                    $assignment->questions[$key]['submission_z_score'] = isset($mean_and_std_dev_by_question_submissions[$question->id])
                        ? $this->computeZScore($submission_score, $mean_and_std_dev_by_question_submissions[$question->id])
                        : 'N/A';
                }
                if ($assignment->assessment_type === 'learning tree') {
                    $assignment->questions[$key]['percent_penalty'] = $learning_tree_penalties_by_question_id[$question->id];
                    $assignment->questions[$key]['learning_tree'] = $learning_trees_by_question_id[$question->id];
                    $assignment->questions[$key]['submitted_but_did_not_explore_learning_tree'] = $submitted_but_did_not_explore_learning_tree[$question->id];
                    $assignment->questions[$key]['learning_tree_success_criteria_satisfied'] = $learning_tree_success_criteria_satisfied[$question->id];
                    $assignment->questions[$key]['answered_correctly_at_least_once'] = $answered_correctly_at_least_once;
                    $assignment->questions[$key]['learning_tree_id'] = $learning_tree_ids_by_question_id[$question->id];
                    $assignment->questions[$key]['number_of_resets'] = $number_of_resets_by_question_id[$question->id];
                    $assignment->questions[$key]['reset_count'] = $reset_count;

                }

                $assignment->questions[$key]['last_submitted'] = ($last_submitted !== 'N/A')
                    ? $this->convertUTCMysqlFormattedDateToHumanReadableLocalDateAndTime($last_submitted, Auth::user()->time_zone, 'M d, Y g:i:s a')
                    : $last_submitted;

                $assignment->questions[$key]['late_penalty_percent'] = ($last_submitted !== 'N/A')
                    ? $Submission->latePenaltyPercent($assignment, Carbon::parse($last_submitted))
                    : 0;

                $assignment->questions[$key]['late_question_submission'] = ($last_submitted !== 'N/A')
                    ?
                    $late_question_submission
                    : false;

                $assignment->questions[$key]['submission_count'] = $submission_count;


                $submission_file = $submission_files_by_question_id[$question->id] ?? false;


                if ($submission_file) {

                    $assignment->questions[$key]['open_ended_submission_type'] = $submission_file['open_ended_submission_type'];
                    $assignment->questions[$key]['submission'] = $submission_file['submission'];
                    $assignment->questions[$key]['submission_file_exists'] = (boolean)$assignment->questions[$key]['submission'];

                    $formatted_submission_file_info = $this->getFormattedSubmissionFileInfo($submission_file, $assignment->id, $this);

                    $assignment->questions[$key]['original_filename'] = $formatted_submission_file_info['original_filename'];
                    $assignment->questions[$key]['date_submitted'] = $formatted_submission_file_info['date_submitted'];

                    $assignment->questions[$key]['late_file_submission'] = ($formatted_submission_file_info['date_submitted'] !== 'N/A')
                        ?
                        Carbon::parse($submission_file['date_submitted'])->greaterThan(Carbon::parse($due_date_considering_extension))
                        : false;

                    if ($assignment->show_scores) {
                        $submission_files_score = $formatted_submission_file_info['submission_file_score'];
                        $assignment->questions[$key]['date_graded'] = $formatted_submission_file_info['date_graded'];
                        $assignment->questions[$key]['submission_file_score'] = $submission_files_score;
                        $assignment->questions[$key]['grader_id'] = $submission_files_by_question_id[$question->id]['grader_id'];
                        $assignment->questions[$key]['submission_file_z_score'] = isset($mean_and_std_dev_by_submission_files[$question->id])
                            ? $this->computeZScore($submission_files_score, $mean_and_std_dev_by_submission_files[$question->id])
                            : 'N/A';
                    }
                    if ($assignment->show_scores) {
                        $assignment->questions[$key]['file_feedback_exists'] = $formatted_submission_file_info['file_feedback_exists'];
                        $assignment->questions[$key]['file_feedback'] = $formatted_submission_file_info['file_feedback'];
                        $assignment->questions[$key]['text_feedback'] = $formatted_submission_file_info['text_feedback'];

                        $assignment->questions[$key]['file_feedback_url'] = null;
                        $formatted_submission_file_info['file_feedback'] = null;
                        if ($formatted_submission_file_info['file_feedback_exists']) {
                            $assignment->questions[$key]['file_feedback_url'] = $formatted_submission_file_info['file_feedback_url'];
                            $assignment->questions[$key]['file_feedback_type'] = (pathinfo($formatted_submission_file_info['file_feedback'], PATHINFO_EXTENSION) === 'mpga') ? 'audio' : 'file';
                        }
                    }
                    //for PDFS we can set the page
                    $page = $submission_files_by_question_id[$question->id]['page']
                        ? "#page=" . $submission_files_by_question_id[$question->id]['page']
                        : '';

                    $assignment->questions[$key]['submission_file_url'] = $formatted_submission_file_info['temporary_url'] . $page;
                    $assignment->questions[$key]['submission_file_page'] = $submission_files_by_question_id[$question->id]['page']
                        ? $submission_files_by_question_id[$question->id]['page']
                        : null;


                }
                if ($assignment->show_scores) {
                    $total_score = floatval($assignment->questions[$key]['submission_file_score'] ?? 0)
                        + floatval($assignment->questions[$key]['submission_score'] ?? 0);
                    $assignment->questions[$key]['total_score'] = round(min(floatval($points[$question->id]), $total_score), 4);
                }

                $local_solution_exists = isset($solutions_by_question_id[$question->id]['solution_file_url']);
                $assignment->questions[$key]['answer_html'] = !$local_solution_exists && (Auth::user()->role === 2 || $show_solution) ? $question->addTimeToS3Images($assignment->questions[$key]->answer_html, $domd) : null;
                $assignment->questions[$key]['solution_html'] = !$local_solution_exists && (Auth::user()->role === 2 || $show_solution) ? $question->addTimeToS3Images($assignment->questions[$key]->solution_html, $domd) : null;
                $seed = in_array($question->technology, ['webwork', 'imathas', 'qti'])
                    ? $this->getAssignmentQuestionSeed($assignment, $question, $questions_for_which_seeds_exist, $seeds_by_question_id, $question->technology)
                    : '';

                if ($show_solution || Auth::user()->role === 2) {
                    $assignment->questions[$key]['solution'] = $solutions_by_question_id[$question->id]['original_filename'] ?? false;
                    $assignment->questions[$key]['solution_type'] = $solutions_by_question_id[$question->id]['solution_type'] ?? false;
                    $assignment->questions[$key]['solution_file_url'] = $solutions_by_question_id[$question->id]['solution_file_url'] ?? false;
                    $assignment->questions[$key]['solution_text'] = $solutions_by_question_id[$question->id]['solution_text'] ?? false;

                    if (($assignment->questions[$key]['answer_html'] || $assignment->questions[$key]['solution_html']) && !$assignment->questions[$key]['solution_type']) {
                        $assignment->questions[$key]['solution_type'] = 'html';
                    }
                    $assignment->questions[$key]['qti_answer_json'] = $question->qti_json ? $question->formatQtiJson($question->qti_json, $seed, true) : null;
                }
                $assignment->questions[$key]['text_question'] = Auth::user()->role === 2 ? $question->addTimeToS3Images($assignment->questions[$key]->text_question, $domd) : null;
                $shown_hint = $assignment->can_view_hint && (Auth::user()->role === 2 || (Auth::user()->role === 3 && in_array($question->id, $shown_hints)));
                $assignment->questions[$key]['shown_hint'] = $shown_hint;
                $assignment->questions[$key]['hint_exists'] = $assignment->questions[$key]->hint !== null && $assignment->questions[$key]->hint !== '';
                $assignment->questions[$key]['hint'] = $shown_hint
                    ? $question->addTimeToS3Images($assignment->questions[$key]->hint, $domd)
                    : null;

                $assignment->questions[$key]['notes'] = Auth::user()->role === 2 ? $question->addTimeToS3Images($assignment->questions[$key]->notes, $domd) : null;


                $show_webwork_correct_incorrect_table = $assignment->assessment_type === 'real time' || $assignment->solutions_released;
                $technology_src_and_problemJWT = $question->getTechnologySrcAndProblemJWT($request, $assignment, $question, $seed, $show_webwork_correct_incorrect_table, $domd, $JWE);
                $technology_src = $technology_src_and_problemJWT['technology_src'];
                $problemJWT = $technology_src_and_problemJWT['problemJWT'];
                $a11y_question = null;
                $a11y_technology_src = '';
                if ((Auth::user()->role === 2 || (Auth::user()->role === 3 && $a11y)) && $question->a11y_technology_id) {
                    $a11y_question = $question->replicate();
                    $a11y_question->technology = $question->a11y_technology;
                    $a11y_question->technology_iframe = $a11y_question->getTechnologyIframeFromTechnology($a11y_question->a11y_technology, $a11y_question->a11y_technology_id);
                    $a11y_technology_src_and_problemJWT = $a11y_question->getTechnologySrcAndProblemJWT($request, $assignment, $a11y_question, $seed, $show_webwork_correct_incorrect_table, $domd, $JWE);
                    $a11y_technology_src = $a11y_technology_src_and_problemJWT['technology_src'];
                    $a11y_problemJWT = $a11y_technology_src_and_problemJWT['problemJWT'];

                }

                if ($technology_src) {
                    $assignment->questions[$key]->iframe_id = $this->createIframeId();
                    //don't return if not available yet!
                    $assignment->questions[$key]->technology_iframe = !(Auth::user()->role === 3 && !Auth::user()->fake_student) || ($assignment->shown && time() >= strtotime($assignment->assignToTimingByUser('available_from')))
                        ? $this->formatIframeSrc($question['technology_iframe'], $assignment->questions[$key]->iframe_id, $problemJWT)
                        : '';
                    $assignment->questions[$key]->technology_src = Auth::user()->role === 2 ? $technology_src : '';

                    if ($a11y_question) {
                        $assignment->questions[$key]->a11y_technology_iframe = !(Auth::user()->role === 3 && !Auth::user()->fake_student) || ($assignment->shown && time() >= strtotime($assignment->assignToTimingByUser('available_from')))
                            ? $this->formatIframeSrc($a11y_question->technology_iframe, $assignment->questions[$key]->iframe_id, $a11y_problemJWT)
                            : '';
                        $assignment->questions[$key]->a11y_technology_src = Auth::user()->role === 2 ? $a11y_technology_src : '';
                        if (Auth::user()->role === 3 && $a11y) {
                            $assignment->questions[$key]->technology_iframe = $a11y_technology_src;
                            $assignment->questions[$key]->technology_src = $a11y_technology_src;

                        }

                    }


                }

                $assignment->questions[$key]->qti_json = $assignment->questions[$key]->technology === 'qti'
                    ? $question->formatQtiJson($question['qti_json'], $seed, false)
                    : null;


                //Frankenstein type problems

                $assignment->questions[$key]->non_technology_iframe_src = $this->getLocallySavedPageIframeSrc($question);
                $assignment->questions[$key]->has_auto_graded_and_open_ended = $iframe_technology && $assignment->questions[$key]['open_ended_submission_type'] !== '0';
            }
            $response['type'] = 'success';
            $response['questions'] = $assignment->questions->values();

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the assignment questions.  Please try again or contact us for assistance.";
        }

        return $response;
    }


    function getRandomlyChosenQuestions(Assignment $assignment, User $user)
    {
        $randomly_chosen_questions = RandomizedAssignmentQuestion::where('assignment_id', $assignment->id)
            ->where('user_id', $user->id)
            ->select('question_id')
            ->get()
            ->pluck('question_id')
            ->toArray();
        if (!$randomly_chosen_questions) {
            $numbers = range(0, count($assignment->questions) - 1);
            shuffle($numbers);
            $randomly_chosen_question_keys = array_slice($numbers, 0, $assignment->number_of_randomized_assessments);
            $question_ids = $assignment->questions->pluck('id')->toArray();
            foreach ($randomly_chosen_question_keys as $randomly_chosen_question_key) {
                $question_id = $question_ids[$randomly_chosen_question_key];
                $randomizedAssignmentQuestion = new RandomizedAssignmentQuestion();
                $randomizedAssignmentQuestion->assignment_id = $assignment->id;
                $randomizedAssignmentQuestion->question_id = $question_id;
                $randomizedAssignmentQuestion->user_id = $user->id;
                $randomizedAssignmentQuestion->save();
                $randomly_chosen_questions[] = $question_id;
            }
        }
        return $randomly_chosen_questions;
    }

    /**
     * @throws Exception
     */
    public
    function getAssignmentQuestionSeed(Assignment $assignment,
                                       Question   $question,
                                       array      $questions_for_which_seeds_exist,
                                       array      $seeds_by_question_id,
                                       string     $technology)
    {
        if (in_array($question->id, $questions_for_which_seeds_exist)) {
            $seed = $seeds_by_question_id[$question->id];
        } else {
            switch ($technology) {
                case('webwork'):
                    $seed = $assignment->algorithmic ? rand(1, 99999) : config('myconfig.webwork_seed');
                    break;
                case('imathas'):
                    $seed = $assignment->algorithmic ? rand(1, 99999) : config('myconfig.imathas_seed');
                    break;
                case('qti'):
                    $qti_array = json_decode($question->qti_json, true);
                    $question_type = $qti_array['questionType'];
                    $seed = '';
                    if (in_array($question_type, ['true_false', 'fill_in_the_blank','numerical'])) {
                        return $seed;
                    }
                    switch ($question_type) {
                        case('matching'):
                            $seed = [];
                            foreach ($qti_array['possibleMatches'] as $possible_match){
                                $seed[] = $possible_match['identifier'];
                            }
                            shuffle($seed);
                            $seed = json_encode($seed);
                            break;
                        case('select_choice'):
                            $seed = [];
                            foreach ($qti_array['inline_choice_interactions'] as $identifier => $choices) {
                                $indices = range(0, count($choices) - 1);
                                shuffle($indices);
                                $seed[$identifier] = $indices;
                            }
                            $seed = json_encode($seed);
                            break;
                        case('multiple_choice'):
                        case('multiple_answers'):
                            $seed = [];
                            $choices = $qti_array['simpleChoice'];
                            shuffle($choices);
                            foreach ($choices as $choice) {
                                $seed[] = $choice['identifier'];
                            }
                            $seed = json_encode($seed);
                            break;
                        default:
                            throw new Exception("QTI $question_type does not generate a seed.");
                    }
                    break;
                default:
                    throw new Exception("$technology should not be generating a seed.");
            }
            DB::table('seeds')->insert([
                'assignment_id' => $assignment->id,
                'question_id' => $question->id,
                'user_id' => Auth::user()->id,
                'seed' => $seed,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);

        }
        return $seed;
    }

    function getOtherRandomizedQuestionId(array $user_question_ids, array $question_ids, int $question_id_to_remove)
    {
        foreach ($question_ids as $question_id) {
            if (($question_id !== $question_id_to_remove) && !in_array($question_id, $user_question_ids)) {
                return $question_id;
            }
        }
        return false;

    }

    /**
     * @param $assignment
     * @param $Submission
     * @param $submission
     * @param $question
     * @return bool
     */
    function showRealTimeSolution($assignment, $Submission, $submission, $question)
    {
        if (!$submission) {
            return false;
        }
        $real_time_show_solution = false;
        if ($assignment->assessment_type === 'real time'
            && $assignment->scoring_type === 'p'
            && $assignment->solutions_availability === 'automatic') {
            //can view if either they got it right OR they ask to view it (unlimited) OR they
            $attempt = json_decode($submission->submission);
            $proportion_correct = $Submission->getProportionCorrect($question->technology, $attempt);
            $answered_correctly = $question->technology !== 'text' && (abs($proportion_correct - 1) < PHP_FLOAT_EPSILON);
            if (!$answered_correctly) {
                $real_time_show_solution = $assignment->number_of_allowed_attempts === 'unlimited'
                    ? $submission->show_solution
                    : $submission->submission_count >= $assignment->number_of_allowed_attempts;
            } else {
                $real_time_show_solution = true;
            }
        }
        return $real_time_show_solution;
    }


}
