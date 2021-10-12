<?php

namespace App\Http\Controllers;

use App\AssignmentSyncQuestion;
use App\CompiledPDFOverride;
use App\Course;
use App\Grader;
use App\GraderNotification;
use App\Helpers\Helper;
use App\Http\Requests\UpdateScoresRequest;
use App\Http\Requests\UpdateSubmisionFilePage;
use App\Question;
use App\QuestionLevelOverride;
use App\Score;
use App\User;
use App\AssignmentFile;
use App\SubmissionFile;
use App\Assignment;

use App\Extension;
use App\Traits\S3;
use App\Traits\DateFormatter;
use App\Traits\GeneralSubmissionPolicy;
use App\Traits\LatePolicy;
use App\Traits\SubmissionFiles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;


use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

use App\Exceptions\Handler;
use \Exception;


class SubmissionFileController extends Controller
{

    use S3;
    use DateFormatter;
    use GeneralSubmissionPolicy;
    use LatePolicy;
    use SubmissionFiles;


    /**
     * @var int
     */
    private $max_number_of_uploads_allowed;
    /**
     * @var int
     */
    private $max_file_size;

    public function __construct()
    {
        $this->max_number_of_uploads_allowed = 15;
        $this->max_file_size = 24000000;///really just 20MB but extra wiggle room
    }

    public function updateScores(UpdateScoresRequest $request,
                                 Assignment          $assignment,
                                 Question            $question,
                                 SubmissionFile      $submissionFile,
                                 Score               $score): array
    {
        return $score->handleUpdateScores($request, $assignment, $question, $submissionFile);

    }

    /**
     * @param Course $course
     * @param GraderNotification $graderNotification
     * @param Assignment $assignment
     * @param SubmissionFile $submissionFile
     * @return array
     * @throws Exception
     */
    public function getUngradedSubmissions(Course             $course,
                                           GraderNotification $graderNotification,
                                           Assignment         $assignment,
                                           SubmissionFile     $submissionFile): array
    {
        $authorized = Gate::inspect('getUngradedSubmissions', [$submissionFile, $course]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {

            $now = Carbon::now()->subDay()->format('Y-m-d H:i:s');
            $where = "date_graded IS NULL
                      AND due < '$now'
                      AND TYPE != 'a'
                      AND courses.id = '$course->id'";


            $sql = $graderNotification->submissionSQL($where);

            $ungraded_submissions = DB::select(DB::raw($sql));
            $process_ungraded_submissions = $graderNotification->processUngradedSubmissions($ungraded_submissions, $assignment);
            $formatted_ungraded_submissions_by_grader = $process_ungraded_submissions['formatted_ungraded_submissions_by_grader'];
            $graders_by_id = $process_ungraded_submissions['graders_by_id'];

            $formatted_ungraded_submissions_by_course = '';
            foreach ($formatted_ungraded_submissions_by_grader as $grader_id => $formatted_ungraded_submission) {
                $formatted_ungraded_submissions_by_course .= "<p><strong>{$graders_by_id[$grader_id]['first_name']} {$graders_by_id[$grader_id]['last_name']}</strong></p>";
                $formatted_ungraded_submissions_by_course .= $formatted_ungraded_submission . '<br><hr>';

            }

            $response['ungraded_submissions'] = $formatted_ungraded_submissions_by_course;
            $response['type'] = 'success';

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the ungraded submissions.  Please try again by refreshing the page or contact us for assistance.";
        }
        return $response;
    }

    /**
     * @param UpdateSubmisionFilePage $request
     * @param Assignment $assignment
     * @param Question $question
     * @param SubmissionFile $submissionFile
     * @param Extension $extension
     * @param AssignmentSyncQuestion $assignmentSyncQuestion
     * @return array
     * @throws Exception
     */
    public function updatePage(UpdateSubmisionFilePage $request,
                               Assignment              $assignment,
                               Question                $question,
                               SubmissionFile          $submissionFile,
                               Extension               $extension,
                               AssignmentSyncQuestion  $assignmentSyncQuestion): array
    {
        $response['type'] = 'error';
        if ($can_upload_response = $this->canSubmitBasedOnGeneralSubmissionPolicy($request->user(), $assignment, $assignment->id, $question->id)) {
            if ($can_upload_response['type'] === 'error') {
                $compiledPDFOverride = new CompiledPDFOverride();
                $has_set_page_override = $compiledPDFOverride->hasSetPageOverride($assignment->id);
                if (!$has_set_page_override) {
                    $response['message'] = $can_upload_response['message'];
                    return $response;
                }
            }

        }
        $data = $request->validated();
        try {
            $page = $data['page'];
            $full_file = $submissionFile->where('assignment_id', $assignment->id)
                ->where('user_id', Auth::user()->id)
                ->where('type', 'a')
                ->first();

            $submission_file_data = ['type' => 'q',
                'submission' => $full_file->submission,
                'original_filename' => $full_file->original_filename,
                'page' => $page,
                'file_feedback' => null,
                'text_feedback' => null,
                'date_graded' => null,
                'score' => null,
                'date_submitted' => Carbon::now()];
            $is_update = DB::table('submission_files')
                ->where('user_id', Auth::user()->id)
                ->where('assignment_id', $assignment->id)
                ->where('question_id', $question->id)
                ->first();
            $submissionFile->updateOrCreate(
                ['user_id' => Auth::user()->id,
                    'assignment_id' => $assignment->id,
                    'question_id' => $question->id,
                    'type' => 'q'],
                $submission_file_data
            );
            $open_ended_score = $this->updateScoreIfCompletedScoringType($assignment, $question->id);
            $auto_graded_submission = DB::table('submissions')->where('user_id', Auth::user()->id)
                ->where('assignment_id', $assignment->id)
                ->where('question_id', $question->id)
                ->first();
            $total_score = $open_ended_score + ($auto_graded_submission ? $auto_graded_submission->score : 0);


            $response['completed_all_assignment_questions'] = $assignmentSyncQuestion->completedAllAssignmentQuestions($assignment);
            $response['original_filename'] = $full_file->original_filename;
            $response['late_file_submission'] = $this->isLateSubmission($extension, $assignment, Carbon::now());
            $response['submission_file_url'] = $this->getTemporaryUrl($assignment->id, $full_file->submission) . "#page=$page";
            $response['total_score'] = Helper::removeZerosAfterDecimal($total_score);
            $response['date_submitted'] = $this->convertUTCMysqlFormattedDateToHumanReadableLocalDateAndTime(date('Y-m-d H:i:s'), Auth::user()->time_zone);
            $re = $is_update ? 're-' : '';
            $response['message'] = "You have {$re}assigned Page $page as the start of the submitted solution to Question $request->question_number.";
            $response['page'] = $page;
            $response['type'] = 'success';


        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to set the page for this question.  Please try again or contact us for assistance.";


        }
        return $response;
    }

    /**
     * @param Request $request
     * @param Assignment $assignment
     * @param Question $question
     * @param User $studentUser
     * @param SubmissionFile $submissionFile
     * @param Grader $grader
     * @return array
     * @throws Exception
     */
    public function getFilesFromS3(Request        $request,
                                   Assignment     $assignment,
                                   Question       $question,
                                   User           $studentUser,
                                   SubmissionFile $submissionFile,
                                   Grader         $grader)
    {
        $response['type'] = 'error';

        $authorized = Gate::inspect('getFilesFromS3', [$submissionFile, $assignment, $studentUser, $grader]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        $open_ended_submission_type = $request->open_ended_submission_type;

        try {
            $submission_file_info = $submissionFile->where('assignment_id', $assignment->id)
                ->where('question_id', $question->id)
                ->where('user_id', $studentUser->id)
                ->first();

            $response['files'] = ['submission_text' => null,
                'submission_url' => null,
                'submission' => null];
            $file_feedback = null;

            if ($submission_file_info) {
                $submission = $submission_file_info['submission'];
                $response['files']['submission'] = $submission;
                $file_feedback = $submission_file_info['file_feedback'];
                $is_text = ($request->user()->role === 3)
                    ? $open_ended_submission_type === 'text'
                    : pathinfo($submission, PATHINFO_EXTENSION) === 'html';
                if ($is_text) {
                    try {
                        $submission_text = Storage::disk('s3')->get("assignments/{$assignment->id}/$submission");
                    } catch (Exception $e) {
                        $submission_text = "Error retrieving your text submission: " . $e->getMessage();
                    }
                    $response['files']['submission_text'] = $submission_text;
                    $response['files']['submission_url'] = null;
                } else {

                    $response['files']['submission_url'] = $this->getTemporaryUrl($assignment->id, $submission);
                    $response['files']['submission_text'] = null;


                }
            }
            $response['files']['file_feedback_url'] = $file_feedback ? $this->getTemporaryUrl($assignment->id, $file_feedback) : null;

            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to retrieve the file submissions for this assignment.  Please try again or contact us for assistance.";
        }

        return $response;


    }


    public
    function downloadSubmissionFile(Request $request, AssignmentFile $assignmentFile, SubmissionFile $submissionFile)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('downloadAssignmentFile', [$assignmentFile, $submissionFile, $request->assignment_id, $request->submission]);


        try {
            if (!$authorized->allowed()) {
                throw new Exception($authorized->message());
            }
            return Storage::disk('s3')->download("assignments/$request->assignment_id/$request->submission");
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            return null;
        }
    }

    public
    function getTemporaryUrlFromRequest(Request $request, AssignmentFile $assignmentFile, Assignment $assignment)
    {
        $response['type'] = 'error';

        $course = $assignment->find($request->assignment_id)->course;
        $authorized = Gate::inspect('createTemporaryUrl', [$assignmentFile, $course]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $response['temporary_url'] = $this->getTemporaryUrl($request->assignment_id, $request->file);
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to retrieve the file.  Please try again or contact us for assistance.";
        }
        return $response;
    }


    /**
     * @param Request $request
     * @return array
     * @throws Exception
     */
    public function canSubmitFileSubmission(Request $request)
    {
        try {
            $assignment_id = $request->assignmentId;
            $assignment = Assignment::find($assignment_id);
            $question_id = $request->questionId;
            $upload_level = $request->uploadLevel;
            $user = Auth::user();
            $user_id = $user->id;
            $response['type'] = 'error';
            //validator put here because I wasn't usingf vform so had to manually handle errors

            if ($can_upload_response = $this->canSubmitBasedOnGeneralSubmissionPolicy($user, $assignment, $assignment_id, $question_id, $upload_level)) {
                if ($can_upload_response['type'] === 'error') {
                    $compiledPDFOverride = new CompiledPDFOverride();
                    $questionLevelOverride = new QuestionLevelOverride();
                    $has_upload_override = $upload_level === 'assignment'
                        ? $compiledPDFOverride->hasCompiledPDFOverride($assignment_id)
                        : $questionLevelOverride->hasOpenEndedOverride($assignment_id, $question_id);
                    if (!$has_upload_override) {
                        $response['message'] = $can_upload_response['message'];
                        return $response;
                    }
                }
            }

            $latest_submission = DB::table('submission_files')
                ->where('type', 'q') //not needed but for completeness
                ->where('assignment_id', $assignment_id)
                ->where('question_id', $question_id)
                ->where('user_id', $user_id)
                ->select('upload_count')
                ->first();

            $upload_count = is_null($latest_submission) ? 0 : $latest_submission->upload_count;


            if ($upload_count + 1 > $this->max_number_of_uploads_allowed) {
                $response['message'] = 'You have exceeded the number of times that you can re-upload a file submission.';
                return $response;

            }
            $response['type'] = 'success';
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to validate permission to upload this file. Please try again or contact us for assistance.";
        }
        return $response;


    }

    /**
     * @param Request $request
     * @param Extension $extension
     * @param SubmissionFile $submissionFile
     * @param AssignmentSyncQuestion $assignmentSyncQuestion
     * @return array
     * @throws Exception
     */


    function storeSubmissionFile(Request                $request,
                                 Extension              $extension,
                                 SubmissionFile         $submissionFile,
                                 AssignmentSyncQuestion $assignmentSyncQuestion): array
    {

        $response['type'] = 'error';

        if (!$request->s3_key) {
            $response['message'] = "It looks like you might be using an outdated file uploader.  Please refresh the page in your Browser to get Adapt's most up-to-date version then try again.";
            return $response;
        }

        $assignment_id = $request->assignmentId;
        $question_id = $request->questionId;
        $upload_level = $request->uploadLevel;
        $user = Auth::user();
        $user_id = $user->id;

        $can_submit_response = $this->canSubmitFileSubmission($request);
        if ($can_submit_response['type'] === 'error') {
            $response['message'] = $can_submit_response['message'];
            return $response;
        }


        try {

            $file_size = Storage::disk('s3')->size($request->s3_key);
            if ($file_size > $this->max_file_size) {
                $response['message'] = 'Your file is ' . $this->bytesToHuman($file_size) . ' and has exceeded the ' . $this->bytesToHuman($this->max_file_size) . '  limit.';
                return $response;
            }
            $assignment = Assignment::find($assignment_id);
            //validator put here because I wasn't using vform so had to manually handle errors

            $latest_submission = DB::table('submission_files')
                ->where('type', 'q') //not needed but for completeness
                ->where('assignment_id', $assignment_id)
                ->where('question_id', $question_id)
                ->where('user_id', $user_id)
                ->select('upload_count')
                ->first();

            $upload_count = is_null($latest_submission) ? 0 : $latest_submission->upload_count;


            $submission = $request->s3_key;
            $original_filename = $request->original_filename;
            $s3_file_contents = Storage::disk('s3')->get($request->s3_key);
            Storage::disk('local')->put($submission, $s3_file_contents);


            $submission_file_data = ['type' => $upload_level[0],
                'submission' => basename($submission),
                'original_filename' => $original_filename,
                'file_feedback' => null,
                'text_feedback' => null,
                'date_graded' => null,
                'score' => null,
                'upload_count' => $upload_count + 1,
                'date_submitted' => Carbon::now()];
            DB::beginTransaction();

            switch ($upload_level) {
                case('assignment'):

                    $submissionFile->updateOrCreate(
                        ['user_id' => $user_id,
                            'assignment_id' => $assignment_id,
                            'type' => 'a'],
                        $submission_file_data
                    );
                    $response['message'] = "You can now set the pages for your open-ended submissions.";
                    $response['full_pdf_url'] = $this->getTemporaryUrl($assignment_id, basename($submission));
                    break;
                case('question'):
                    $submissionFile->updateOrCreate(
                        ['user_id' => Auth::user()->id,
                            'assignment_id' => $assignment_id,
                            'question_id' => $question_id,
                            'type' => 'q'],
                        $submission_file_data
                    );
                    $score = $this->updateScoreIfCompletedScoringType($assignment, $question_id);

                    $response['submission'] = basename($submission);
                    $response['original_filename'] = $original_filename;
                    $response['score'] = $score === null ? null : $score;
                    $response['submission_file_url'] = $this->getTemporaryUrl($assignment_id, basename($submission));
                    $response['date_submitted'] = $this->convertUTCMysqlFormattedDateToHumanReadableLocalDateAndTime(date('Y-m-d H:i:s'), Auth::user()->time_zone, 'M d, Y g:i:s a');
                    $response['message'] = "Your file submission has been saved.";
                    $response['completed_all_assignment_questions'] = $assignmentSyncQuestion->completedAllAssignmentQuestions($assignment);
                    break;
            }

            $response['late_file_submission'] = $this->isLateSubmission($extension, $assignment, Carbon::now());
            if (($upload_count >= $this->max_number_of_uploads_allowed - 3)) {
                $response['message'] .= "  You may resubmit " . ($this->max_number_of_uploads_allowed - (1 + $upload_count)) . " more times.";
            }
            $response['type'] = 'success';
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to save your file submission. Please try again and if the problem persists, contact us.";
        }
        return $response;

    }


    public
    function storeFileFeedback(Request $request, AssignmentFile $assignmentFile, User $user, Assignment $assignment)
    {

        $response['type'] = 'error';
        $assignment_id = $request->assignmentId;
        $question_id = $request->questionId;
        $student_user_id = $request->userId;


        $authorized = Gate::inspect('uploadFileFeedback', [$assignmentFile, $user->find($student_user_id), $assignment->find($assignment_id)]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        if (!$question_id) {
            $response['message'] = "You are missing the question id.";
            return $response;
        }
        try {
            //validator put here because I wasn't using vform so had to manually handle errors

            $validator = Validator::make($request->all(), [
                'fileFeedback' => $this->fileValidator()
            ]);

            if ($validator->fails()) {
                $response['message'] = $validator->errors()->first('fileFeedback');
                return $response;
            }

            //save locally and to S3
            $fileFeedback = $request->file('fileFeedback')->store("assignments/$assignment_id", 'local');
            $feedbackContents = Storage::disk('local')->get($fileFeedback);
            Storage::disk('s3')->put($fileFeedback, $feedbackContents, ['StorageClass' => 'STANDARD_IA']);

            DB::table('submission_files')
                ->where('user_id', $student_user_id)
                ->where('assignment_id', $assignment_id)
                ->where('question_id', $question_id)
                ->update(['file_feedback' => basename($fileFeedback)]);

            $response['type'] = 'success';
            $response['message'] = 'Your feedback file has been saved.';
            $response['file_feedback_url'] = $this->getTemporaryUrl($assignment_id, basename($fileFeedback));
            $response['file_feedback_type'] = 'q';


        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to save your feedback file.  Please try again or contact us for assistance.";
        }
        return $response;

    }
}
