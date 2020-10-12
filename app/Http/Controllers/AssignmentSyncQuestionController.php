<?php

namespace App\Http\Controllers;

use App\Exceptions\Handler;
use App\JWE;
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
use Illuminate\Support\Facades\Log;

use App\Traits\S3;
use App\Traits\SubmissionFiles;
use App\Traits\JWT;

class AssignmentSyncQuestionController extends Controller
{

    use IframeFormatter;
    use DateFormatter;
    use S3;
    use SubmissionFiles;
    use JWT;

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

    public function updateLastSubmittedAndLastResponse(Request $request, Assignment $assignment, Question $question)
    {
        /**helper function to get the response info from server side technologies...*/

        $submission = DB::table('submissions')
            ->where('question_id', $question->id)
            ->where('assignment_id', $assignment->id)
            ->where('user_id', Auth::user()->id)
            ->first();


        $submissions_by_question_id[$question->id] = $submission;
        $question_technologies[$question->id] = Question::find($question->id)->technology;
        $response_info = $this->getResponseInfo($submissions_by_question_id, $question_technologies, $question->id);

        return ['last_submitted' => $this->convertUTCMysqlFormattedDateToHumanReadableLocalDateAndTime($response_info['last_submitted'], Auth::user()->time_zone),
            'student_response' => $response_info['student_response']
        ];
    }

    public function getResponseInfo($submissions_by_question_id, $question_technologies, $question_id)
    {
        $student_response = 'N/A';
        $correct_response = null;
        $submission_score = 0;
        $last_submitted = 'N/A';
        if (isset($submissions_by_question_id[$question_id])) {
            $submission = $submissions_by_question_id[$question_id];
            $last_submitted = $submission->updated_at;
            $submission_object = json_decode($submission->submission);
            $submission_score = $submission->score;
            switch ($question_technologies[$question_id]) {
                case('h5p'):
                    $student_response = $submission_object->result->response;
                    //$correct_response = $submission_object->object->definition->correctResponsesPattern;
                    break;
                case('webwork'):
                    $student_response = 'N/A';
                    $student_response_arr = [];
                    $session_JWT = $this->getPayload($submission_object->sessionJWT);

                    if ($session_JWT->answersSubmitted) {
                        $answer_template = (array)$session_JWT->answerTemplate;
                        foreach ($answer_template as $key => $value) {
                            if (is_numeric($key)) {
                                $student_response_arr[$key] = $value->answer->student_ans;
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
        return compact('student_response', 'correct_response', 'submission_score', 'last_submitted');

    }


    public function getQuestionsToView(Request $request, Assignment $assignment, Submission $Submission, SubmissionFile $SubmissionFile)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('view', $assignment);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $assignment_question_info = $this->getQuestionInfoByAssignment($assignment);

            $question_ids = [];
            $question_files = [];
            $points = [];
            $solutions_by_question_id = [];
            if (!$assignment_question_info['questions']) {
                $response['type'] = 'success';
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
                $solutions_by_question_id[$question->question_id] = false;//assume they don't exist
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
            //  dd($question_ids);
            $submissions_by_question_id = [];
            if ($submissions) {
                foreach ($submissions as $key => $value) {
                    $submissions_by_question_id[$value->question_id] = $value;
                }
            }

            if ($assignment->solutions_released || Auth::user()->role === 2) {

                $solutions = DB::table('solutions')
                    ->whereIn('question_id', $question_ids)
                    ->where('user_id',$assignment->course->user_id)
                    ->get();
                //  dd($question_ids);
                if ($solutions) {
                    foreach ($solutions as $key => $value) {
                        $solutions_by_question_id[$value->question_id]= $value->original_filename;
                    }
                }
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
            $JWE = new JWE();
            foreach ($assignment->questions as $key => $question) {
                $iframe_technology = true;//assume there's a technology --- will be set to false once there isn't
                $assignment->questions[$key]['points'] = $points[$question->id];

                $response_info = $this->getResponseInfo($submissions_by_question_id, $question_technologies, $question->id);

                $student_response = $response_info['student_response'];
                $correct_response = $response_info['correct_response'];
                $submission_score = $response_info['submission_score'];
                $last_submitted = $response_info['last_submitted'];


                $assignment->questions[$key]['student_response'] = $student_response;
                if ($assignment->solutions_released) {
                    $assignment->questions[$key]['correct_response'] = $correct_response;
                    $assignment->questions[$key]['submission_score'] = $submission_score;
                }

                $assignment->questions[$key]['last_submitted'] = ($last_submitted !== 'N/A')
                    ? $this->convertUTCMysqlFormattedDateToHumanReadableLocalDateAndTime($last_submitted, Auth::user()->time_zone)
                    : $last_submitted;
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
                $submission_file_score = $has_question_files ? ($formatted_submission_file_info['submission_file_score'] ?? 0) : 0;
                $assignment->questions[$key]['total_score'] = min(floatval($points[$question->id]), floatval($submission_score) + floatval($submission_file_score));

                $assignment->questions[$key]['solution'] = $solutions_by_question_id[$question->id]
                    ? $solutions_by_question_id[$question->id]
                    : false;

                //set up the problemJWT
                $custom_claims = ['adapt' => [
                    'scheme_and_host' => $request->getSchemeAndHttpHost(),
                    'assignment_id' => $assignment->id,
                    'question_id' => $question->id,
                    'technology' => $question->technology]];
                $custom_claims["{$question->technology}"] = '';
                switch ($question->technology) {
                    case('webwork'):
                        $custom_claims['webwork'] = [];
                        $custom_claims['webwork']['problemSeed'] = '1234567';
                        $custom_claims['webwork']['courseID'] = 'daemon_course';
                        $custom_claims['webwork']['userID'] = 'daemon';
                        $custom_claims['webwork']['course_password'] = 'daemon';
                        $custom_claims['webwork']['showSummary'] = 1;
                        $custom_claims['webwork']['displayMode'] = 'MathJax';
                        $custom_claims['webwork']['language'] = 'en';
                        $custom_claims['webwork']['outputformat'] = 'libretexts';
                        $custom_claims['webwork']['showCorrectButton'] = 0;
                        $src = $this->getIframeSrcFromHtml($domd, $question['technology_iframe']);
                        $custom_claims['webwork']['sourceFilePath'] = $this->getQueryParamFromSrc($src, 'sourceFilePath');
                        $custom_claims['webwork']['answersSubmitted'] = '0';
                        $custom_claims['webwork']['displayMode'] = 'MathJax';
                        $custom_claims['webwork']['form_action_url'] = 'https://demo.webwork.rochester.edu/webwork2/html2xml';
                        $custom_claims['webwork']['problemUUID'] = rand(1, 1000);
                        $custom_claims['webwork']['language'] = 'en';
                        $custom_claims['webwork']['showHints'] = 1;
                        $custom_claims['webwork']['showSolution'] = 1;
                        $custom_claims['webwork']['showDebug'] = 0;

                        $question['technology_iframe'] = '<iframe class="webwork_problem" frameborder=0 src="https://demo.webwork.rochester.edu/webwork2/html2xml?" width="100%"></iframe>';
                        $problemJWT = \JWTAuth::customClaims($custom_claims)->fromUser(Auth::user());
                        break;
                    case('imathas'):
                        $custom_claims['imathas'] = [];
                        $src = $this->getIframeSrcFromHtml($domd, $question['technology_iframe']);
                        $custom_claims['imathas']['id'] = $this->getQueryParamFromSrc($src, 'id');
                        $custom_claims['imathas']['seed'] = 1234;
                        $question['technology_iframe'] = '<iframe class="imathas_problem" src="https://imathas.libretexts.org/imathas/adapt/embedq2.php?" height="1500" width="100%"></iframe>';
                        $question['technology_iframe'] = '<div id="embed1wrap" style="overflow:visible;position:relative">
 <iframe id="embed1" style="position:absolute;z-index:1" frameborder=0 src="https://imathas.libretexts.org/imathas/adapt/embedq2.php?frame_id=embed1"></iframe>
</div>';
                        $problemJWT = $JWE->encode(\JWTAuth::customClaims($custom_claims)->fromUser(Auth::user()));

                        break;
                    case('h5p'):
                        //NOT USED FOR anything at the moment
                        $problemJWT = \JWTAuth::customClaims($custom_claims)->fromUser(Auth::user());
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
                }
                if (isset($instructor_learning_trees_by_question_id[$question->id])) {
                    $assignment->questions[$key]->learning_tree = $instructor_learning_trees_by_question_id[$question->id];
                } elseif (isset($other_instrutor_learning_trees_by_question_id[$question->id])) {
                    $assignment->questions[$key]->learning_tree = $other_instructor_learning_trees_by_question_id[$question->id];
                } else {
                    $assignment->questions[$key]->learning_tree = '';
                }

                //Frankenstein type problems

                $assignment->questions[$key]->non_technology_iframe_src = $question['non_technology'] ? $request->root() . "/storage/{$question['page_id']}.html" : '';
            }


            $response['type'] = 'success';
            $response['questions'] = $assignment->questions;

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the assignment questions.  Please try again or contact us for assistance.";
        }

        return $response;
    }
}
