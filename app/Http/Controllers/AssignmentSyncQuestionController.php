<?php

namespace App\Http\Controllers;

use App\Exceptions\Handler;
use App\JWE;
use App\Traits\QueryFiles;
use \Exception;

use Illuminate\Http\Request;
use App\Http\Requests\updateAssignmentQuestionPointsRequest;
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
    use QueryFiles;
    use LatePolicy;

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
        $authorized = Gate::inspect('toggleQuestionFiles', [$assignmentSyncQuestion, $assignment]);

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
                ->update(['points' => $data['points']]);
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
      if ($assignment->assessment_type === 'real time' ) {
           $solution = DB::table('solutions')
               ->where('question_id', $question->id)
               ->where('user_id', $assignment->course->user_id)
               ->first();
           if ($solution){
               $original_filename = $solution->original_filename;
           }
       }
        return ['last_submitted' => $this->convertUTCMysqlFormattedDateToHumanReadableLocalDateAndTime($response_info['last_submitted'],
            Auth::user()->time_zone, 'M d, Y g:i:s a'),
            'student_response' => $response_info['student_response'],
            'submission_count' => $response_info['submission_count'],
            'submission_score' => $response_info['submission_score'],
            'late_penalty_percent' => $response_info['late_penalty_percent'],
            'late_question_submission' => $response_info['late_question_submission'],
            'solution' =>  $original_filename
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
        if (isset($submissions_by_question_id[$question_id])) {
            $submission = $submissions_by_question_id[$question_id];
            $last_submitted = $submission->updated_at;
            $submission_object = json_decode($submission->submission);
            $submission_score = $submission->score;
            $submission_count = $submission->submission_count;
            $late_penalty_percent = $Submission->latePenaltyPercent($assignment, Carbon::parse($last_submitted));
            $late_question_submission = $this->isLateSubmission($Extension, $assignment, Carbon::parse($last_submitted));


            switch ($question_technologies[$question_id]) {
                case('h5p'):
                    $student_response = $submission_object->result->response ? $submission_object->result->response : 'N/A';
                    //$correct_response = $submission_object->object->definition->correctResponsesPattern;
                    break;
                case('webwork'):
                    $student_response = 'N/A';
                    $student_response_arr = [];
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
        return compact('student_response', 'correct_response', 'submission_score', 'last_submitted', 'submission_count', 'late_penalty_percent', 'late_question_submission');

    }


    public function getQuestionsToView(Request $request, Assignment $assignment, Submission $Submission, SubmissionFile $SubmissionFile, Extension $Extension)
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
            $due_date_considering_extension = $assignment->due;

            if ($extension) {
                if (Carbon::parse($extension)>Carbon::parse($assignment->due)) {
                    $due_date_considering_extension = $extension;
                }
            }


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
            $submission_files_by_question_and_user = $SubmissionFile->getUserAndQuestionFileInfo($assignment,'allStudents', $user_as_collection);
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

            //if they've already explored the learning tree, then we can let them view it right at the start
            if ($assignment->assessment_type === 'learning tree') {
                foreach ($assignment->learningTrees() as $value) {
                    $submission_exists_by_question_id = isset($submissions_by_question_id[$value->question_id]) && $submissions_by_question_id[$value->question_id]->submission_count >= 1;
                    $learning_trees_by_question_id[$value->question_id] =
                        $submission_exists_by_question_id
                            ? json_decode($value->learning_tree)->blocks
                            : null;
                    $learning_tree_penalties_by_question_id[$value->question_id] = $submission_exists_by_question_id
                        ? min((($submissions_by_question_id[$value->question_id]->submission_count - 1) * $assignment->submission_count_percent_decrease), 100) . '%'
                        : '0%';
                }
            }


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
                //  dd($question_ids);
                if ($solutions) {
                    foreach ($solutions as $key => $value) {
                        $solutions_by_question_id[$value->question_id] = $value->original_filename;
                    }
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

                $response_info = $this->getResponseInfo($assignment, $Extension, $Submission, $submissions_by_question_id, $question_technologies, $question->id);

                $student_response = $response_info['student_response'];
                $correct_response = $response_info['correct_response'];
                $submission_score = $response_info['submission_score'];
                $last_submitted = $response_info['last_submitted'];
                $submission_count = $response_info['submission_count'];
                $late_question_submission = $response_info['late_question_submission'];


                $assignment->questions[$key]['student_response'] = $student_response;
                $show_solution = ($assignment->assessment_type !== 'real time' && $assignment->solutions_released)
                    || ($assignment->assessment_type === 'real time' && $submission_count);
                if ($show_solution) {
                    $assignment->questions[$key]['correct_response'] = $correct_response;
                }

                if ($assignment->show_scores) {
                    $assignment->questions[$key]['submission_score'] = $submission_score;
                }
                if ($assignment->assessment_type === 'learning tree') {
                    $assignment->questions[$key]['percent_penalty'] = $learning_tree_penalties_by_question_id[$question->id];
                    $assignment->questions[$key]['learning_tree'] = $learning_trees_by_question_id[$question->id];
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
                $has_question_files = $question_files[$question->id];

                $assignment->questions[$key]['questionFiles'] = $has_question_files;//camel case because using in vue
                if ($has_question_files) {
                    $submission_file = $submission_files_by_question_id[$question->id] ?? false;

                    $assignment->questions[$key]['submission'] = $submission_file['submission'];
                    $assignment->questions[$key]['submission_file_exists'] = (boolean)$assignment->questions[$key]['submission'];

                    $formatted_submission_file_info = $this->getFormattedSubmissionFileInfo($submission_file, $assignment->id, $this);

                    $assignment->questions[$key]['original_filename'] = $formatted_submission_file_info['original_filename'];
                    $assignment->questions[$key]['date_submitted'] = $formatted_submission_file_info['date_submitted'];

                    $assignment->questions[$key]['late_file_submission'] = ($formatted_submission_file_info['date_submitted'] !== 'N/A')
                        ?
                        Carbon::parse($submission_file['date_submitted'] )->greaterThan(Carbon::parse($due_date_considering_extension))
                        : false;

                    if ($assignment->show_scores) {
                        $assignment->questions[$key]['date_graded'] = $formatted_submission_file_info['date_graded'];
                        $assignment->questions[$key]['submission_file_score'] = $formatted_submission_file_info['submission_file_score'];
                        $assignment->questions[$key]['grader_id'] = $submission_files_by_question_id[$question->id]['grader_id'];
                    }
                    if ($assignment->solutions_released) {
                        $assignment->questions[$key]['file_feedback_exists'] = $formatted_submission_file_info['file_feedback_exists'];
                        $assignment->questions[$key]['file_feedback'] = $formatted_submission_file_info['file_feedback'];
                        $assignment->questions[$key]['text_feedback'] = $formatted_submission_file_info['text_feedback'];
                    }
                    if (!$got_first_temporary_url) {
                        $assignment->questions[$key]['submission_file_url'] = $formatted_submission_file_info['temporary_url'];
                        $assignment->questions[$key]['file_feedback_url'] = $formatted_submission_file_info['file_feedback_url'];
                        $got_first_temporary_url = true;
                    }
                }
                $submission_file_score = $has_question_files ? ($formatted_submission_file_info['submission_file_score'] ?? 0) : 0;
                if ($assignment->show_scores) {
                    $assignment->questions[$key]['total_score'] = round(min(floatval($points[$question->id]), floatval($submission_score) + floatval($submission_file_score)), 2);
                }

                $assignment->questions[$key]['solution'] = $solutions_by_question_id[$question->id] && $show_solution
                    ? $solutions_by_question_id[$question->id]
                    : false;

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

                        $webwork_url = 'webwork.libretexts.org';
                        //$webwork_url = 'demo.webwork.rochester.edu';

                        $seed = $this->getAssignmentQuestionSeed($assignment, $question, $questions_for_which_seeds_exist, $seeds_by_question_id, 'webwork');

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

                        $custom_claims['webwork']['showSummary'] = 1;
                        $custom_claims['webwork']['displayMode'] = 'MathJax';
                        $custom_claims['webwork']['language'] = 'en';
                        $custom_claims['webwork']['outputformat'] = 'libretexts';
                        $custom_claims['webwork']['showCorrectButton'] = 0;
                        $src = $this->getIframeSrcFromHtml($domd, $question['technology_iframe']);
                        $custom_claims['webwork']['sourceFilePath'] = $this->getQueryParamFromSrc($src, 'sourceFilePath');
                        $custom_claims['webwork']['answersSubmitted'] = '0';
                        $custom_claims['webwork']['displayMode'] = 'MathJax';
                        $custom_claims['webwork']['form_action_url'] = "https://$webwork_url/webwork2/html2xml";
                        $custom_claims['webwork']['problemUUID'] = rand(1, 1000);
                        $custom_claims['webwork']['language'] = 'en';
                        $custom_claims['webwork']['showHints'] = 0;
                        $custom_claims['webwork']['showSolution'] = 0;
                        $custom_claims['webwork']['showDebug'] = 0;

                        $question['technology_iframe'] = '<iframe class="webwork_problem" frameborder=0 src="https://' . $webwork_url . '/webwork2/html2xml?" width="100%"></iframe>';

                        $problemJWT = $this->createProblemJWT($JWE, $custom_claims, 'webwork');

                        break;
                    case('imathas'):

                        $custom_claims['webwork'] = [];
                        $custom_claims['imathas'] = [];
                        $src = $this->getIframeSrcFromHtml($domd, $question['technology_iframe']);
                        $custom_claims['imathas']['id'] = $this->getQueryParamFromSrc($src, 'id');

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


                //Frankenstein type problems

                $assignment->questions[$key]->non_technology_iframe_src = $this->getLocallySavedQueryPageIframeSrc($question);

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

    public
    function createProblemJWT(JWE $JWE, array $custom_claims, string $technology)
    {
        $payload = auth()->payload();
        $secret = $JWE->getSecret($technology);
        \JWTAuth::getJWTProvider()->setSecret($secret); //change the secret
        $token = \JWTAuth::getJWTProvider()->encode(array_merge($custom_claims, $payload->toArray())); //create the token
        $problemJWT = $JWE->encrypt($token, 'webwork'); //create the token
        //put back the original secret
        \JWTAuth::getJWTProvider()->setSecret(env('JWT_SECRET'));
        $payload = auth()->payload();

        return $problemJWT;

    }

    public
    function getAssignmentQuestionSeed(Assignment $assignment, Question $question, array $questions_for_which_seeds_exist, array $seeds_by_question_id, string $technology)
    {

        if (in_array($question->id, $questions_for_which_seeds_exist)) {
            $seed = $seeds_by_question_id[$question->id];
        } else {
            switch ($technology) {
                case('webwork'):
                    $seed = env('WEBWORK_SEED');
                    break;
                case('imathas'):
                    $seed = env('IMATHAS_SEED');
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
}
