<?php

namespace App\Http\Controllers;

use App\DataShop;
use App\Exceptions\Handler;
use App\Http\Requests\StartClickerAssessment;
use App\Http\Requests\UpdateOpenEndedSubmissionType;
use App\JWE;
use App\Libretext;
use App\LtiLaunch;
use App\LtiGradePassback;
use App\RandomizedAssignmentQuestion;
use App\Solution;
use App\Traits\LibretextFiles;
use App\Traits\Statistics;
use App\User;
use Carbon\CarbonImmutable;
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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

use App\Traits\S3;
use App\Traits\SubmissionFiles;
use App\Traits\GeneralSubmissionPolicy;
use App\Traits\LatePolicy;
use App\Traits\JWT;
use Carbon\Carbon;

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


    public function storeOpenEndedDefaultText(Request $request, Assignment $assignment, Question $question, AssignmentSyncQuestion $assignmentSyncQuestion)
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
    public function order(Request $request, Assignment $assignment, AssignmentSyncQuestion $assignmentSyncQuestion)
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

    public function getClickerQuestion(Request $request, Assignment $assignment)
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

    public function startClickerAssessment(StartClickerAssessment $request, Assignment $assignment, Question $question, AssignmentSyncQuestion $assignmentSyncQuestion)
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
            $response['message'] = 'You students can begin submitting responses.';

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
    public function getQuestionIdsByAssignment(Assignment $assignment)
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
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the assignment questions.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    public function getQuestionSummaryByAssignment(Assignment $assignment, Solution $solutions)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('view', $assignment);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {

            //Get all assignment questions Question Upload, Solution, Number of Points
            $assignment_questions = DB::table('assignment_question')
                ->join('questions', 'assignment_question.question_id', '=', 'questions.id')
                ->where('assignment_id', $assignment->id)
                ->orderBy('order')
                ->select('assignment_question.*',
                    'questions.library',
                    'questions.page_id',
                    'questions.technology_iframe',
                    'questions.technology',
                    'questions.title', DB::raw('questions.id AS question_id'))
                ->get();

            $question_ids = [];
            foreach ($assignment_questions as $key => $value) {
                $question_ids[] = $value->question_id;
            }

            $assignment_solutions = $solutions->whereIn('question_id', $question_ids)
                ->where('user_id', Auth::user()->id)
                ->get();


            $assignment_solutions_by_question_id = [];
            $rows = [];
            foreach ($assignment_solutions as $key => $value) {
                $assignment_solutions_by_question_id[$value->question_id] = ['original_filename' => $value->original_filename,
                    'file' => $value->file];
            }

            foreach ($assignment_questions as $key => $value) {
                $columns = [];
                $columns['title'] = $value->title;
                if (!$value->title) {
                    $Libretext = new Libretext(['library' => $value->library]);
                    try {
                        $contents = $Libretext->getContentsByPageId($value->page_id);
                    } catch (Exception $e) {

                    }
                    $columns['title'] = $contents['@title'] ?? 'Private title: contact us';
                    Question::where('id', $value->question_id)->update(['title' => $columns['title']]);
                }
                if ($value->open_ended_submission_type === 'text') {
                    $value->open_ended_submission_type = $value->open_ended_text_editor . ' text';
                }
                $submission = [];
                if ($value->technology !== 'text') {
                    $submission[] = $value->technology;
                }
                if ($value->open_ended_submission_type) {
                    $submission[] = ucwords($value->open_ended_submission_type);
                }
                if (!$submission) {
                    $submission = ['Nothing to submit'];
                }
                $columns['submission'] = implode(', ', $submission);
                $columns['points'] = $this->formatDecimals($value->points);
                $columns['solution'] = $this->_getSolutionLink($assignment, $assignment_solutions_by_question_id, $value->question_id);
                $columns['order'] = $value->order;
                $columns['question_id'] = $value->question_id;
                $columns['technology'] = $value->technology;
                $columns['assignment_id_question_id'] = "{$assignment->id}-{$value->question_id}";
                $rows[] = $columns;
            }

            $response['type'] = 'success';
            $response['rows'] = $rows;


        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the questions summary for this assignment.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    private function _getSolutionLink($assignment, $assignment_solutions_by_question_id, $question_id)
    {
        return isset($assignment_solutions_by_question_id[$question_id]) ?
            '<a href="' . Storage::disk('s3')->temporaryUrl("solutions/{$assignment->course->user_id}/{$assignment_solutions_by_question_id[$question_id]['file']}", now()->addMinutes(360)) . '" target="_blank">' . $assignment_solutions_by_question_id[$question_id]['original_filename'] . '</a>'
            : 'None';
    }

    public function getQuestionInfoByAssignment(Assignment $assignment)
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
                    $question_info->points = $this->formatDecimals($question_info->points);

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

    public function updateOpenEndedSubmissionType(UpdateOpenEndedSubmissionType $request, Assignment $assignment, Question $question, AssignmentSyncQuestion $assignmentSyncQuestion)
    {

        $response['type'] = 'error';

        $authorized = Gate::inspect('updateOpenEndedSubmissionType', [$assignmentSyncQuestion, $assignment]);

        if (!$authorized->allowed()) {

            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $data = $request->validated();
            $open_ended_text_editor = null;
            if ((strpos($data['open_ended_submission_type'], 'text') !== false)) {
                $open_ended_text_editor = str_replace(' text', '', $data['open_ended_submission_type']);
                $data['open_ended_submission_type'] = 'text';

            }
            DB::table('assignment_question')->where('assignment_id', $assignment->id)
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


    public function updatePoints(UpdateAssignmentQuestionPointsRequest $request, Assignment $assignment, Question $question, AssignmentSyncQuestion $assignmentSyncQuestion)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('update', [$assignmentSyncQuestion, $assignment]);
        $data = $request->validated();

        if (!$authorized->allowed()) {

            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $is_randomized_assignment = $assignment->number_of_randomized_assessments;

            if ($is_randomized_assignment) {
                DB::table('assignment_question')
                    ->where('assignment_id', $assignment->id)
                    ->update(['points' => $data['points']]);
                $assignment->default_points_per_question = $data['points'];
                $assignment->save();
                $message = 'Since this is a randomized assignment, all question points have been updated to the same value.';
            } else {
                DB::table('assignment_question')
                    ->where('assignment_id', $assignment->id)
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

    public function store(Assignment $assignment, Question $question, AssignmentSyncQuestion $assignmentSyncQuestion)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('add', [$assignmentSyncQuestion, $assignment]);

        if (!$authorized->allowed()) {

            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            DB::beginTransaction();
            DB::table('assignment_question')
                ->insert([
                    'assignment_id' => $assignment->id,
                    'question_id' => $question->id,
                    'order' => $assignmentSyncQuestion->getNewQuestionOrder($assignment),
                    'points' => $assignment->default_points_per_question, //don't need to test since tested already when creating an assignment
                    'open_ended_submission_type' => $assignment->default_open_ended_submission_type,
                    'open_ended_text_editor' => $assignment->default_open_ended_text_editor]);
            DB::commit();
            $response['type'] = 'success';
            $response['message'] = 'The question has been added to the assignment.';
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error adding the question to the assignment.  Please try again or contact us for assistance.";
        }

        return $response;

    }


    public function destroy(Assignment $assignment, Question $question, AssignmentSyncQuestion $assignmentSyncQuestion, LtiLaunch $ltiLaunch, LtiGradePassback $ltiGradePassback)
    {


        $response['type'] = 'error';
        $authorized = Gate::inspect('delete', [$assignmentSyncQuestion, $assignment, $question]);


        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
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

                }

                ///get all assignment questions
                /// get all students for which this affects
                /// add a different question


                $response['message'] = "As this is a randomized assignment, please ask your students to revisit their assignment as a question may have been updated.";
            }

            $this->updateAssignmentScoreBasedOnRemovedQuestion($assignment, $question, $ltiLaunch, $ltiGradePassback);
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
            DB::table('assignment_question_learning_tree')
                ->where('assignment_question_id', $assignment_question_id)
                ->delete();
            DB::table('assignment_question')->where('question_id', $question->id)
                ->where('assignment_id', $assignment->id)
                ->delete();
            DB::table('randomized_assignment_questions')->where('assignment_id', $assignment->id)
                ->where('question_id', $question->id)
                ->delete();
            $currently_ordered_questions = DB::table('assignment_question')
                ->where('assignment_id', $assignment->id)
                ->orderBy('order')
                ->get();

            if ($currently_ordered_questions) {
                $currently_ordered_question_ids = [];
                foreach ($currently_ordered_questions as $key => $question) {
                    $currently_ordered_question_ids[] = $question->question_id;
                }
                $assignmentSyncQuestion->orderQuestions($currently_ordered_question_ids, $assignment);
            }


            DB::commit();
            $response['type'] = 'success';
            $response['message'] = 'The question has been removed from the assignment.';
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error removing the question from the assignment.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    public function updateAssignmentScoreBasedOnRemovedQuestion(Assignment $assignment, Question $question, LtiLaunch $ltiLaunch, LtiGradePassback $ltiGradePassback)
    {

        $scores = DB::table('scores')->where('assignment_id', $assignment->id)
            ->select('user_id', 'score')
            ->get();

        $lti_launches = DB::table('lti_launches')->where('assignment_id', $assignment->id)
            ->select('user_id', 'launch_id')
            ->get();

        //just remove the one...
        $submissions = DB::table('submissions')->where('question_id', $question->id)
            ->where('assignment_id', $assignment->id)
            ->select('user_id', 'score')
            ->get();
        $submissions_by_user_id = [];
        foreach ($submissions as $submission) {
            $submissions_by_user_id[$submission->user_id] = $submission->score;
        }
        $submission_files = DB::table('submission_files')->where('question_id', $question->id)
            ->where('assignment_id', $assignment->id)
            ->where('score', '<>', null)
            ->select('user_id', 'score')
            ->get();
        $submission_files_by_user_id = [];
        foreach ($submission_files as $submission_file) {
            $submission_files_by_user_id[$submission_file->user_id] = $submission_file->score;
        }
        $lti_launches_by_user_id = [];
        foreach ($lti_launches as $lti_launch) {
            $lti_launches_by_user_id[$lti_launch->user_id] = $lti_launch->launch_id;
        }
        foreach ($scores as $score) {
            $submission_file_score = $submission_files_by_user_id[$score->user_id] ?? 0;
            $submission_score = $submissions_by_user_id[$score->user_id] ?? 0;
            $new_score = $score->score - $submission_file_score - $submission_score;
            DB::table('scores')->where('assignment_id', $assignment->id)
                ->where('user_id', $score->user_id)
                ->update(['score' => $new_score]);
            if (isset($lti_launches_by_user_id[$score->user_id])) {
                $ltiGradePassback->passBackByUserIdAndAssignmentId($assignment, $score->user_id, $new_score, $ltiLaunch);
            }
        }

    }


    public function getIframeSrcFromHtml(\DOMDocument $domd, string $html)
    {
        libxml_use_internal_errors(true);//errors from DOM that I don't care about
        $domd->loadHTML($html);
        libxml_use_internal_errors(false);
        $iFrame = $domd->getElementsByTagName('iframe')->item(0);
        return $iFrame->getAttribute('src');

    }

    public function getQueryParamFromSrc(string $src, string $query_param)
    {
        $url_components = parse_url($src);
        parse_str($url_components['query'], $output);
        return $output[$query_param];
    }

    public function updateLastSubmittedAndLastResponse(Request $request, Assignment $assignment, Question $question, Submission $Submission, Extension $Extension)
    {
        /**helper function to get the response info from server side technologies...*/

        $submission = DB::table('submissions')
            ->where('question_id', $question->id)
            ->where('assignment_id', $assignment->id)
            ->where('user_id', Auth::user()->id)
            ->first();


        $submissions_by_question_id[$question->id] = $submission;
        $question_technologies[$question->id] = Question::find($question->id)->technology;
        $response_info = $this->getResponseInfo($assignment, $Extension, $Submission, $submissions_by_question_id, $question_technologies, $question->id);
        $original_filename = null;
        if ($assignment->assessment_type === 'real time') {
            $solution = DB::table('solutions')
                ->where('question_id', $question->id)
                ->where('user_id', $assignment->course->user_id)
                ->first();
            if ($solution) {
                $original_filename = $solution->original_filename;
            }
        }
        try {
            session()->get('submission_id');
            DataShop::where('session_id', session()->get('submission_id'))
                ->update(['input' => $response_info['student_response']]);

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);

        }
        return ['last_submitted' => $this->convertUTCMysqlFormattedDateToHumanReadableLocalDateAndTime($response_info['last_submitted'],
            Auth::user()->time_zone, 'M d, Y g:i:s a'),
            'student_response' => $response_info['student_response'],
            'submission_count' => $response_info['submission_count'],
            'submission_score' => $response_info['submission_score'],
            'late_penalty_percent' => $response_info['late_penalty_percent'],
            'late_question_submission' => $response_info['late_question_submission'],
            'answered_correctly_at_least_once' => $response_info['answered_correctly_at_least_once'],
            'solution' => $original_filename
        ];

    }

    public function getResponseInfo(Assignment $assignment, Extension $Extension, Submission $Submission, $submissions_by_question_id, $question_technologies, $question_id)
    {
        $student_response = 'N/A';
        $correct_response = null;
        $score = null;
        $late_penalty_percent = 0;
        $submission_score = 0;
        $last_submitted = 'N/A';
        $submission_count = 0;
        $late_question_submission = false;
        $answered_correctly_at_least_once = 0;
        if (isset($submissions_by_question_id[$question_id])) {
            $submission = $submissions_by_question_id[$question_id];
            $last_submitted = $submission->updated_at;
            $submission_object = json_decode($submission->submission);
            $submission_score = $submission->score;
            $submission_count = $submission->submission_count;
            $late_penalty_percent = $Submission->latePenaltyPercent($assignment, Carbon::parse($last_submitted));
            $late_question_submission = $this->isLateSubmission($Extension, $assignment, Carbon::parse($last_submitted));
            $answered_correctly_at_least_once = $submission->answered_correctly_at_least_once;

            switch ($question_technologies[$question_id]) {
                case('h5p'):
                    //Log::info(json_encode($submission_object->result));
                    $student_response = 'N/A';
                    if (isset($submission_object->result->response)) {
                        if (isset($submission_object->object->definition) && $submission_object->object->definition->interactionType === 'choice') {
                            $choices = $submission_object->object->definition->choices;
                            foreach ($choices as $choice) {
                                if ((int)$choice->id === (int)$submission_object->result->response) {
                                    $student_response = $choice->description->{'en-US'};
                                }
                            }
                        } else {
                            $student_response = $submission_object->result->response;
                        }

                    }

                    //$correct_response = $submission_object->object->definition->correctResponsesPattern;
                    break;
                case('webwork'):
                    $student_response = 'N/A';
                    $student_response_arr = [];

                    if (isset($submission_object->platform) && $submission_object->platform === 'standaloneRenderer')
                    {
                        $answers_arr = json_decode(json_encode($submission_object->score->answers),true);
                        //AnSwEr0003
                        foreach ( $answers_arr as $answer_key => $value){
                            $numeric_key = (int) ltrim(str_replace('AnSwEr', '',$answer_key),0);
                            $student_response_arr[ $numeric_key ]= $value['original_student_ans'];
                        }

                    } else {
                        $session_JWT = $this->getPayload($submission_object->sessionJWT);
                        //session_JWT will be null for bad submissions
                        if (is_object($session_JWT) && $session_JWT->answersSubmitted) {
                            $answer_template = (array)$session_JWT->answerTemplate;
                            foreach ($answer_template as $key => $value) {
                                if (is_numeric($key)) {
                                    $student_response_arr[$key] = $value->answer->student_ans;
                                }
                            }
                        }
                    }
                    if ($student_response_arr) {
                        ksort($student_response_arr);//order by keys
                        $student_response = implode(',', $student_response_arr);
                    }

                    break;
                case('imathas'):
                    $tks = explode('.', $submission_object->state);
                    list($headb64, $bodyb64, $cryptob64) = $tks;
                    $state = json_decode(base64_decode($bodyb64));

                    $student_response = json_encode($state->stuanswers);
                    //$correct_response = 'N/A';
                    $last_submitted = $submission->updated_at;
                    break;

            }
        }
        return compact('student_response', 'correct_response', 'submission_score', 'last_submitted', 'submission_count', 'late_penalty_percent', 'late_question_submission', 'answered_correctly_at_least_once');

    }


    public function getQuestionsToView(Request $request, Assignment $assignment, Submission $Submission, SubmissionFile $SubmissionFile, Extension $Extension, AssignmentSyncQuestion $assignmentSyncQuestion)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('view', $assignment);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {

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
            $explored_learning_tree = [];
            $open_ended_submission_types = [];
            $open_ended_text_editors = [];
            $open_ended_default_texts = [];
            $clicker_status = [];
            $clicker_time_left = [];
            $learning_tree_ids_by_question_id = [];


            foreach ($assignment_question_info['questions'] as $question) {
                $question_ids[$question->question_id] = $question->question_id;
                $open_ended_submission_types[$question->question_id] = $question->open_ended_submission_type;
                $open_ended_text_editors[$question->question_id] = $question->open_ended_text_editor;
                $open_ended_default_texts[$question->question_id] = $question->open_ended_default_text;
                $points[$question->question_id] = $question->points;
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
                ->whereIn('id', $question_ids)
                ->get();

            $question_technologies = [];
            foreach ($question_info as $key => $question) {
                $question_technologies[$question->id] = $question->technology;
            }

            //these question_ids come from the assignment
            //in case an instructor accidentally assigns the same problem twice I added in assignment_id
            $submissions = DB::table('submissions')
                ->whereIn('question_id', $question_ids)
                ->where('user_id', Auth::user()->id)
                ->where('assignment_id', $assignment->id)
                ->get();


            $submissions_by_question_id = [];
            if ($submissions) {
                foreach ($submissions as $key => $value) {
                    $submissions_by_question_id[$value->question_id] = $value;
                }
            }

            //if they've already explored the learning tree, then we can let them view it right at the start
            if ($assignment->assessment_type === 'learning tree') {
                foreach ($assignment->learningTrees() as $value) {
                    $learning_tree_ids_by_question_id[$value->question_id] = $value->learning_tree_id;
                    $submission_exists_by_question_id = isset($submissions_by_question_id[$value->question_id]) && $submissions_by_question_id[$value->question_id]->submission_count >= 1;
                    $learning_trees_by_question_id[$value->question_id] =
                        $submission_exists_by_question_id
                            ? json_decode($value->learning_tree)->blocks
                            : null;
                    $learning_tree_penalties_by_question_id[$value->question_id] = $submission_exists_by_question_id
                        ? min((($submissions_by_question_id[$value->question_id]->submission_count - 1) * $assignment->submission_count_percent_decrease), 100) . '%'
                        : '0%';
                    $submitted_but_did_not_explore_learning_tree[$value->question_id] = $submission_exists_by_question_id && ($submissions_by_question_id[$value->question_id]->explored_learning_tree === null);
                    $explored_learning_tree[$value->question_id] = $submission_exists_by_question_id && $submissions_by_question_id[$value->question_id]->explored_learning_tree !== null;
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

            if ($assignment->solutions_released || Auth::user()->role === 2) {

                $solutions = DB::table('solutions')
                    ->whereIn('question_id', $question_ids)
                    ->where('user_id', $assignment->course->user_id)
                    ->get();

                if ($solutions) {
                    foreach ($solutions as $key => $value) {
                        $solutions_by_question_id[$value->question_id]['original_filename'] = $value->original_filename;
                        $solutions_by_question_id[$value->question_id]['solution_text'] = $value->text;
                        $solutions_by_question_id[$value->question_id]['solution_type'] = $value->type;
                        $solutions_by_question_id[$value->question_id]['solution_file_url'] = \Storage::disk('s3')->temporaryUrl("solutions/{$assignment->course->user_id}/{$value->file}", now()->addMinutes(360));

                    }
                }
            }

            $domd = new \DOMDocument();
            $JWE = new JWE();

            $randomly_chosen_questions = [];
            if ($assignment->number_of_randomized_assessments && $request->user()->role == 3) {
                $randomly_chosen_questions = $this->getRandomlyChosenQuestions($assignment, $request->user());
            }

            foreach ($assignment->questions as $key => $question) {
                if ($assignment->number_of_randomized_assessments
                    && $request->user()->role == 3
                    && !in_array($question->id, $randomly_chosen_questions)) {
                    $assignment->questions->forget($key);
                    continue;
                }
                $iframe_technology = true;//assume there's a technology --- will be set to false once there isn't
                $technology_src = '';
                $assignment->questions[$key]['library'] = $question->library;
                $assignment->questions[$key]['page_id'] = $question->page_id;
                $assignment->questions[$key]['title'] = $question->title;
                $assignment->questions[$key]['clicker_status'] = $clicker_status[$question->id];
                $assignment->questions[$key]['clicker_time_left'] = $clicker_time_left[$question->id];
                $assignment->questions[$key]['points'] = $points[$question->id];
                $assignment->questions[$key]['mindtouch_url'] = "https://{$question->library}.libretexts.org/@go/page/{$question->page_id}";

                $response_info = $this->getResponseInfo($assignment, $Extension, $Submission, $submissions_by_question_id, $question_technologies, $question->id);

                $student_response = $response_info['student_response'];
                $correct_response = $response_info['correct_response'];
                $answered_correctly_at_least_once = $response_info['answered_correctly_at_least_once'];
                $submission_score = $this->formatDecimals($response_info['submission_score']);
                $last_submitted = $response_info['last_submitted'];
                $submission_count = $response_info['submission_count'];
                $late_question_submission = $response_info['late_question_submission'];


                $assignment->questions[$key]['student_response'] = $student_response;
                $assignment->questions[$key]['open_ended_submission_type'] = $open_ended_submission_types[$question->id];
                $assignment->questions[$key]['open_ended_text_editor'] = $open_ended_text_editors[$question->id];
                $assignment->questions[$key]['open_ended_default_text'] = $open_ended_default_texts[$question->id];
                $show_solution = ($assignment->assessment_type !== 'real time' && $assignment->solutions_released)
                    || ($assignment->assessment_type === 'real time' && $submission_count);
                if ($show_solution) {
                    $assignment->questions[$key]['correct_response'] = $correct_response;
                }

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
                    $assignment->questions[$key]['explored_learning_tree'] = $explored_learning_tree[$question->id];
                    $assignment->questions[$key]['answered_correctly_at_least_once'] = $answered_correctly_at_least_once;
                    $assignment->questions[$key]['learning_tree_id'] = $learning_tree_ids_by_question_id[$question->id];

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

                    $assignment->questions[$key]['submission_file_url'] = $formatted_submission_file_info['temporary_url'];


                }
                $submission_file_score = $formatted_submission_file_info['submission_file_score'] ?? 0;
                if ($assignment->show_scores) {
                    $assignment->questions[$key]['total_score'] = round(min(floatval($points[$question->id]), floatval($submission_score) + floatval($submission_file_score)), 2);
                }


                $assignment->questions[$key]['solution'] = $solutions_by_question_id[$question->id]['original_filename'] ?? false;
                $assignment->questions[$key]['solution_type'] = $solutions_by_question_id[$question->id]['solution_type'] ?? false;
                $assignment->questions[$key]['solution_file_url'] = $solutions_by_question_id[$question->id]['solution_file_url'] ?? false;
                $assignment->questions[$key]['solution_text'] = $solutions_by_question_id[$question->id]['solution_text'] ?? false;
                //set up the problemJWT
                $custom_claims = ['adapt' => [
                    'assignment_id' => $assignment->id,
                    'question_id' => $question->id,
                    'technology' => $question->technology]];
                $custom_claims['scheme_and_host'] = $request->getSchemeAndHttpHost();
                //if I didn't initialize each, I was getting a weird webwork error
                //in addition, the imathas problem JWT had the webwork info from the previous
                //problem.  Not sure why!  Maybe it has something to do createProblemJWT
                //TymonDesigns keeps the custom claims???
                $custom_claims['imathas'] = [];
                $custom_claims['webwork'] = [];
                $custom_claims['h5p'] = [];
                switch ($question->technology) {

                    case('webwork'):

                        // $webwork_url = 'webwork.libretexts.org';
                        //$webwork_url = 'demo.webwork.rochester.edu';
                        // $webwork_base_url = '';

                        $webwork_url = 'https://prod.adapt.libretexts.org';
                        $webwork_base_url = '/webwork';

                        $seed = $this->getAssignmentQuestionSeed($assignment, $question, $questions_for_which_seeds_exist, $seeds_by_question_id, 'webwork');

                        $custom_claims['iss'] = $request->getSchemeAndHttpHost();//made host dynamic

                        $custom_claims['aud'] = $webwork_url;
                        $custom_claims['webwork']['problemSeed'] = $seed;
                        switch ($webwork_url) {
                            case('demo.webwork.rochester.edu'):
                                $custom_claims['webwork']['courseID'] = 'daemon_course';
                                $custom_claims['webwork']['userID'] = 'daemon';
                                $custom_claims['webwork']['course_password'] = 'daemon';
                                break;
                            case('webwork.libretexts.org'):
                                $custom_claims['webwork']['courseID'] = 'anonymous';
                                $custom_claims['webwork']['userID'] = 'anonymous';
                                $custom_claims['webwork']['course_password'] = 'anonymous';
                                break;
                        }
                        if ($webwork_url === 'https://prod.adapt.libretexts.org') {

                            $custom_claims['webwork']['showPartialCorrectAnswers'] = $assignment->solutions_released;
                            $custom_claims['webwork']['showSummary'] = $assignment->solutions_released;
                            $custom_claims['webwork']['outputFormat'] = 'jwe_secure';
                            // $custom_claims['webwork']['answerOutputFormat'] = 'static';
                            $technology_src = $this->getIframeSrcFromHtml($domd, $question['technology_iframe']);

                            $custom_claims['webwork']['sourceFilePath'] =  $this->getQueryParamFromSrc($technology_src, 'sourceFilePath');
                            $custom_claims['webwork']['sourceFilePath']= (substr($custom_claims['webwork']['sourceFilePath'],0, 4 ) !== "http")
                                ? "https://webwork.libretexts.org/pgfiles/"
                                : '';
                             $custom_claims['webwork']['problemSourceURL'] = "https://webwork.libretexts.org/pgfiles/".$this->getQueryParamFromSrc($technology_src, 'sourceFilePath');

                            $custom_claims['webwork']['JWTanswerURL'] = $request->getSchemeAndHttpHost() ."/api/jwt/process-answer-jwt";

                            $custom_claims['webwork']['problemUUID'] = rand(1, 1000);
                            $custom_claims['webwork']['language'] = 'en';
                            $custom_claims['webwork']['showHints'] = 0;
                            $custom_claims['webwork']['showSolution'] = 0;
                            $custom_claims['webwork']['showDebug'] = 0;

                            $question['technology_iframe'] = '<iframe class="webwork_problem" frameborder=0 src="' . $webwork_url . $webwork_base_url . '/rendered?" width="100%"></iframe>';
                        }
                        else {
                            $custom_claims['webwork']['showSummary'] = 1;
                            $custom_claims['webwork']['displayMode'] = 'MathJax';
                            $custom_claims['webwork']['language'] = 'en';
                            $custom_claims['webwork']['outputformat'] = 'libretexts';
                            $custom_claims['webwork']['showCorrectButton'] = 0;
                            $technology_src = $this->getIframeSrcFromHtml($domd, $question['technology_iframe']);
                            $custom_claims['webwork']['sourceFilePath'] = $this->getQueryParamFromSrc($technology_src, 'sourceFilePath');
                            $custom_claims['webwork']['answersSubmitted'] = '0';
                            $custom_claims['webwork']['displayMode'] = 'MathJax';
                            $custom_claims['webwork']['form_action_url'] = "https://$webwork_url/webwork2/html2xml";
                            $custom_claims['webwork']['problemUUID'] = rand(1, 1000);
                            $custom_claims['webwork']['language'] = 'en';
                            $custom_claims['webwork']['showHints'] = 0;
                            $custom_claims['webwork']['showSolution'] = 0;
                            $custom_claims['webwork']['showDebug'] = 0;
                            $custom_claims['webwork']['showScoreSummary'] = $assignment->solutions_released;
                            $custom_claims['webwork']['showAnswerTable'] = $assignment->solutions_released;

                            $question['technology_iframe'] = '<iframe class="webwork_problem" frameborder=0 src="https://' . $webwork_url . '/webwork2/html2xml?" width="100%"></iframe>';
                        }





                        $problemJWT = $this->createProblemJWT($JWE, $custom_claims, 'webwork');

                        break;
                    case('imathas'):

                        $custom_claims['webwork'] = [];
                        $custom_claims['imathas'] = [];
                        $technology_src = $this->getIframeSrcFromHtml($domd, $question['technology_iframe']);
                        $custom_claims['imathas']['id'] = $this->getQueryParamFromSrc($technology_src, 'id');

                        $seed = $this->getAssignmentQuestionSeed($assignment, $question, $questions_for_which_seeds_exist, $seeds_by_question_id, 'imathas');
                        $custom_claims['imathas']['seed'] = $seed;
                        $custom_claims['imathas']['allowregen'] = false;//don't let them try similar problems
                        $question['technology_iframe'] = '<iframe class="imathas_problem" frameborder="0" src="https://imathas.libretexts.org/imathas/adapt/embedq2.php?" height="1500" width="100%"></iframe>';
                        $question['technology_iframe'] = '<div id="embed1wrap" style="overflow:visible;position:relative">
 <iframe id="embed1" style="position:absolute;z-index:1" frameborder="0" src="https://imathas.libretexts.org/imathas/adapt/embedq2.php?frame_id=embed1"></iframe>
</div>';
                        $problemJWT = $this->createProblemJWT($JWE, $custom_claims, 'webwork');//need to create secret key for imathas as well

                        break;
                    case('h5p'):
                        //NOT USED FOR anything at the moment
                        $custom_claims = [];
                        $problemJWT = \JWTAuth::customClaims($custom_claims)->fromUser(Auth::user());
                        $technology_src = $this->getIframeSrcFromHtml($domd, $question['technology_iframe']);
                        break;
                    case('text'):
                        $iframe_technology = false;
                        break;
                    default:
                        $response['message'] = "Question id {$question->id} uses the technology '{$question->technology}' which is currently not supported by Adapt.";
                        echo json_encode($response);
                        exit;

                }

                if ($iframe_technology) {
                    $assignment->questions[$key]->iframe_id = $this->createIframeId();
                    $assignment->questions[$key]->technology_iframe = $this->formatIframe($question['technology_iframe'], $assignment->questions[$key]->iframe_id, $problemJWT);
                    $assignment->questions[$key]->technology_src = Auth::user()->role === 2 ? $technology_src : '';

                }

                //Frankenstein type problems

                $assignment->questions[$key]->non_technology_iframe_src = $this->getLocallySavedPageIframeSrc($question);
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

    public
    function createProblemJWT(JWE $JWE, array $custom_claims, string $technology)
    {
        $payload = auth()->payload();
        $secret = $JWE->getSecret($technology);
        \JWTAuth::getJWTProvider()->setSecret($secret); //change the secret
        $token = \JWTAuth::getJWTProvider()->encode(array_merge($custom_claims, $payload->toArray())); //create the token
        $problemJWT = $JWE->encrypt($token, 'webwork'); //create the token
        //put back the original secret
        \JWTAuth::getJWTProvider()->setSecret(config('myconfig.jwt_secret'));
        $payload = auth()->payload();

        return $problemJWT;

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

    public
    function getAssignmentQuestionSeed(Assignment $assignment, Question $question, array $questions_for_which_seeds_exist, array $seeds_by_question_id, string $technology)
    {

        if (in_array($question->id, $questions_for_which_seeds_exist)) {
            $seed = $seeds_by_question_id[$question->id];
        } else {
            switch ($technology) {
                case('webwork'):
                    $seed = config('myconfig.webwork_seed');
                    break;
                case('imathas'):
                    $seed = config('myconfig.imathas_seed');
                    break;
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

    /**
     * @param $value_with_decimal
     * @return string
     */
    function formatDecimals($value_with_decimal)
    {
        return rtrim(rtrim($value_with_decimal, "0"), ".");
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


}
