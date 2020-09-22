<?php

namespace App\Http\Controllers;

use App\Exceptions\Handler;
use \Exception;

use Illuminate\Http\Request;
use App\Http\Requests\updateAssignmentQuestionPointsRequest;
use App\Assignment;
use App\Question;
use App\Submission;
use App\SubmissionFile;

use App\Traits\IframeFormatter;
use App\Traits\DateFormatter;
use App\AssignmentSyncQuestion;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use App\Traits\S3;
use App\Traits\SubmissionFiles;

class AssignmentSyncQuestionController extends Controller
{

    use IframeFormatter;
    use DateFormatter;
    use S3;
    use SubmissionFiles;

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
            $assignment_question_info = DB::table('assignment_question')
                ->where('assignment_id', $assignment->id)
                ->get();
            if ($assignment_question_info->isNotEmpty()) {
                foreach ($assignment_question_info as $question_info) {
                    //for getQuestionsByAssignment (internal)
                    $response['questions'][$question_info->question_id] = $question_info;
                    //for the axios call from questions.get.vue
                    $response['question_ids'][] = $question_info->question_id;
                    if ($question_info->question_files) {
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

    public function toggleQuestionFiles(Request $request, Assignment $assignment, Question $question, AssignmentSyncQuestion $assignmentSyncQuestion)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('update', [$assignmentSyncQuestion, $assignment]);

        if (!$authorized->allowed()) {

            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            DB::table('assignment_question')->where('assignment_id', $assignment->id)
                ->where('question_id', $question->id)
                ->update(['question_files' => $request->question_files]);
            $response['type'] = $request->question_files ? 'success' : 'info';
            $response['message'] = $request->question_files ? 'Your students can now upload a question file for this question.'
                : 'Your student can no longer upload a question file for this question.';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error toggling the file upload option.  Please try again or contact us for assistance.";
        }
        return $response;


    }

    public function updatePoints(updateAssignmentQuestionPointsRequest $request, Assignment $assignment, Question $question, AssignmentSyncQuestion $assignmentSyncQuestion)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('update', [$assignmentSyncQuestion, $assignment]);
        $data = $request->validated();

        if (!$authorized->allowed()) {

            $response['message'] = $authorized->message();
            return $response;
        }
        try {

            DB::table('assignment_question')->where('assignment_id', $assignment->id)
                ->where('question_id', $question->id)
                ->update(['points' => $request->points]);
            $response['type'] = 'success';
            $response['message'] = 'The number of points have been updated.';

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
            DB::table('assignment_question')
                ->insert([
                    'assignment_id' => $assignment->id,
                    'question_id' => $question->id,
                    'points' => $assignment->default_points_per_question //don't need to test since tested already when creating an assignment
                ]);
            $response['type'] = 'success';
            $response['message'] = 'The question has been added to the assignment.';
        } catch (Exception $e) {

            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error adding the question to the assignment.  Please try again or contact us for assistance.";
        }

        return $response;

    }

    public function destroy(Assignment $assignment, Question $question, AssignmentSyncQuestion $assignmentSyncQuestion)
    {


        $response['type'] = 'error';
        $authorized = Gate::inspect('delete', [$assignmentSyncQuestion, $assignment]);


        if (!$authorized->allowed()) {

            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $assignment->questions()->detach($question);
            $response['type'] = 'success';
            $response['message'] = 'The question has been removed from the assignment.';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error removing the question from the assignment.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    function getIframeSrcFromHtml(\DOMDocument $domd, string $html)
    {
        libxml_use_internal_errors(true);//errors from DOM that I don't care about
        $domd->loadHTML($html);
        libxml_use_internal_errors(false);
        $iFrame = $domd->getElementsByTagName('iframe')->item(0);
        return $iFrame->getAttribute('src');

    }

    public function getQuestionsToView(Assignment $assignment, Submission $Submission, SubmissionFile $SubmissionFile)
    {


        $response['type'] = 'error';
        $authorized = Gate::inspect('view', $assignment);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $response['type'] = 'success';


            $assignment_question_info = $this->getQuestionInfoByAssignment($assignment);

            $question_ids = [];
            $question_files = [];
            $points = [];
            if (!$assignment_question_info['questions']) {
                $response['questions'] = [];
                return $response;
            }


            $user_as_collection = collect([Auth::user()]);
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


            foreach ($assignment_question_info['questions'] as $question) {
                $question_ids[$question->question_id] = $question->question_id;
                $question_files[$question->question_id] = $question->question_files;
                $points[$question->question_id] = $question->points;
            }


            $instructor_user_id = $assignment->course->user_id;
            $instructor_learning_trees = DB::table('learning_trees')
                ->whereIn('question_id', $question_ids)
                ->where('user_id', $instructor_user_id)
                ->get();
            $instructor_learning_trees_by_question_id = [];
            $other_instructor_learning_trees_by_question_id = [];

            if ($instructor_learning_trees) {
                foreach ($instructor_learning_trees as $key => $value) {
                    $instructor_learning_trees_by_question_id[$value->question_id] = json_decode($value->learning_tree)->blocks;
                }
            }
            $other_instructor_learning_trees = DB::table('learning_trees')
                ->whereIn('question_id', $question_ids)
                ->where('user_id', '<>', Auth::user()->id)
                ->get();
            //just get the first one created

            if ($other_instructor_learning_trees) {
                foreach ($other_instructor_learning_trees as $key => $value) {
                    $other_instructor_learning_trees_by_question_id[$value->question_id] = json_decode($value->learning_tree)->blocks;
                }
            }
//only get the first temporary urls...you'll get the rest onChange page in Vue
            //this way we don't have to make tons of calls to S3 on initial page load
            $got_first_temporary_url = false;
            $domd = new \DOMDocument();
            foreach ($assignment->questions as $key => $question) {
                $assignment->questions[$key]['points'] = $points[$question->id];

                $has_question_files = $question_files[$question->id];
                $assignment->questions[$key]['questionFiles'] = $has_question_files;//camel case because using in vue
                if ($has_question_files) {
                    $submission_file = $submission_files_by_question_id[$question->id] ?? false;
                    $assignment->questions[$key]['submission'] = $submission_file['submission'];
                    $assignment->questions[$key]['submission_file_exists'] = (boolean)$assignment->questions[$key]['submission'];


                    $formatted_submission_file_info = $this->getFormattedSubmissionFileInfo($submission_file, $assignment->id, $this);

                    $assignment->questions[$key]['original_filename'] = $formatted_submission_file_info['original_filename'];
                    $assignment->questions[$key]['date_submitted'] = $formatted_submission_file_info['date_submitted'];
                    $assignment->questions[$key]['date_graded'] = $formatted_submission_file_info['date_graded'];
                    $assignment->questions[$key]['file_feedback_exists'] = $formatted_submission_file_info['file_feedback_exists'];
                    $assignment->questions[$key]['file_feedback'] = $formatted_submission_file_info['file_feedback'];
                    $assignment->questions[$key]['text_feedback'] = $formatted_submission_file_info['text_feedback'];
                    $assignment->questions[$key]['submission_file_score'] = $formatted_submission_file_info['submission_file_score'];
                    if (!$got_first_temporary_url) {
                        $assignment->questions[$key]['submission_file_url'] = $formatted_submission_file_info['temporary_url'];
                        $assignment->questions[$key]['file_feedback_url'] = $formatted_submission_file_info['file_feedback_url'];
                        $got_first_temporary_url = true;
                    }
                }
                $custom_claims = ['adapt' => [
                    'assignment_id' => $assignment->id,
                    'question_id' => $question->id,
                    'technology' => $question->technology]];


                $custom_claims["{$question->technology}"] = '';
                if ($question->technology === 'webwork') {
                    $custom_claims['webwork'] = [];
                    $custom_claims['webwork']['problemSeed'] = '1234567';
                    $custom_claims['webwork']['courseID'] = 'daemon_course';
                    $custom_claims['webwork']['userID'] = 'daemon';
                    $custom_claims['webwork']['course_password'] = 'daemon';
                    $custom_claims['webwork']['showSummary'] = 1;
                    $custom_claims['webwork']['displayMode'] = 'MathJax';
                    $custom_claims['webwork']['language'] = 'en';
                    $custom_claims['webwork']['outputformat'] = 'libretexts';

                    $src = $this->getIframeSrcFromHtml($domd, $question['body']);

                    parse_str($src, $output);
                    $custom_claims['webwork']['sourceFilePath'] = $output['sourceFilePath'];
                    $custom_claims['webwork']['answersSubmitted'] = '0';
                    $custom_claims['webwork']['displayMode'] = 'MathJax';
                    $custom_claims['form_action_url'] = 'https://demo.webwork.rochester.edu/webwork2/html2xml';
                    $custom_claims['webwork']['problemUUID'] = rand(1, 1000);
                    $custom_claims['webwork']['language'] = 'en';
                    $question['body'] = '<iframe class="webwork_problem" src="https://demo.webwork.rochester.edu/webwork2/html2xml?" width="100%"></iframe>';
                }
                $problemJWT = \JWTAuth::customClaims($custom_claims)->fromUser(Auth::user());

                $assignment->questions[$key]->iframe_id = $this->createIframeId();
                $assignment->questions[$key]->body = $this->formatIframe($question['body'], $assignment->questions[$key]->iframe_id, $problemJWT);

                if (isset($instructor_learning_trees_by_question_id[$question->id])) {
                    $assignment->questions[$key]->learning_tree = $instructor_learning_trees_by_question_id[$question->id];
                } elseif (isset($other_instrutor_learning_trees_by_question_id[$question->id])) {
                    $assignment->questions[$key]->learning_tree = $other_instructor_learning_trees_by_question_id[$question->id];
                } else {
                    $assignment->questions[$key]->learning_tree = '';
                }
            }
            $response['questions'] = $assignment->questions;

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the assignment questions.  Please try again or contact us for assistance.";
        }

        return $response;
    }
}
