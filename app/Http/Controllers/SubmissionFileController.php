<?php

namespace App\Http\Controllers;

use App\Cutup;
use App\Enrollment;
use App\Grader;
use App\Http\Requests\StoreTextFeedback;
use App\LtiLaunch;
use App\LtiGradePassback;
use App\Question;
use App\Score;
use App\Section;
use App\User;
use App\AssignmentFile;
use App\SubmissionFile;
use App\Assignment;

use App\Extension;
use App\Traits\S3;
use App\Traits\DateFormatter;
use App\Traits\GeneralSubmissionPolicy;
use App\Traits\LatePolicy;
use App\Http\Requests\StoreScore;
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


    public function getFilesFromS3(Request $request,
                                   Assignment $assignment,
                                   Question $question,
                                   User $studentUser,
                                   SubmissionFile $submissionFile,
                                   Grader $grader)
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
                if ($open_ended_submission_type === 'text') {
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

    /**
     * @param Request $request
     * @param Assignment $assignment
     * @param int $sectionId
     * @param string $gradeView
     * @param Section $section
     * @param Grader $grader
     * @param SubmissionFile $submissionFile
     * @param Enrollment $enrollment
     * @return array
     * @throws Exception
     */
    public function getSubmissionFilesByAssignment(Request $request,
                                                   Assignment $assignment,
                                                   int $sectionId,
                                                   string $gradeView,
                                                   Section $section,
                                                   Grader $grader,
                                                   SubmissionFile $submissionFile,
                                                   Enrollment $enrollment)
    {

        $response['type'] = 'error';

        $authorized = Gate::inspect('viewAssignmentFilesByAssignment', [$submissionFile, $assignment, $sectionId]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $course = $assignment->course;
            $role = Auth::user()->role;
            $enrolled_users = $enrollment->getEnrolledUsersByRoleCourseSection($role, $course, $sectionId);

            $response['type'] = 'success';
            $response['user_and_submission_file_info'] = $enrolled_users->isNotEmpty() ? $submissionFile->getUserAndQuestionFileInfo($assignment, $gradeView, $enrolled_users) : [];

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


    public
    function storeTextFeedback(StoreTextFeedback $request, AssignmentFile $assignmentFile, User $user, Assignment $assignment)
    {
        $response['type'] = 'error';
        $assignment_id = $request->assignment_id;
        $question_id = $request->question_id;
        $student_user_id = $request->user_id;

        $authorized = Gate::inspect('storeTextFeedback', [$assignmentFile, $user->find($student_user_id), $assignment->find($assignment_id)]);


        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        $data = $request->validated();

        try {
            $text_feedback = $request->textFeedback ? trim($request->textFeedback) : '';
            DB::table('submission_files')
                ->where('user_id', $student_user_id)
                ->where('assignment_id', $assignment_id)
                ->where('question_id', $question_id)
                ->update(['text_feedback' => $text_feedback,
                    'text_feedback_editor' => $data['text_feedback_editor'],
                    'date_graded' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                    'grader_id' => Auth::user()->id]);

            $response['type'] = 'success';
            $response['message'] = $text_feedback ? 'Your comments have been saved.' : 'This student will not see any comments.';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to save your assignment submission.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    public
    function storeScore(StoreScore $request,
                        AssignmentFile $assignmentFile,
                        User $user,
                        Assignment $Assignment,
                        Score $score,
                        LtiLaunch $ltiLaunch,
                        LtiGradePassback $ltiGradePassback)
    {


        $response['type'] = 'error';
        $assignment_id = $request->assignment_id;
        $question_id = $request->question_id;
        $student_user_id = $request->user_id;

        $assignment = $Assignment->find($assignment_id);
        $authorized = Gate::inspect('storeScore', [$assignmentFile, $user->find($student_user_id), $assignment]);


        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }


        $max_points = DB::table('assignment_question')
            ->where('question_id', $question_id)
            ->where('assignment_id', $assignment_id)
            ->first()
            ->points;
        $submission_info = DB::table('submissions')
            ->where('question_id', $question_id)
            ->where('assignment_id', $assignment_id)
            ->first();
        $submission_points = $submission_info->score ?? 0;
        if ($submission_points + $request->score > $max_points) {
            $response['message'] = "The total of your Question Submission Score and File Submission score can't be greater than the total number of points for this question.";
            return $response;
        }


        $data = $request->validated();
        try {


            DB::beginTransaction();
            DB::table('submission_files')
                ->where('user_id', $student_user_id)
                ->where('assignment_id', $assignment_id)
                ->where('question_id', $question_id)
                ->update(['score' => $data['score'],
                    'date_graded' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                    'grader_id' => Auth::user()->id]);
            $score->updateAssignmentScore($student_user_id, $assignment_id, $assignment->assessment_type, $ltiLaunch, $ltiGradePassback);
            DB::commit();
            $response['type'] = 'success';
            $response['message'] = 'The score has been saved.';
            $response['grader_name'] = Auth::user()->first_name . ' ' . Auth::user()->last_name;
            $response['date_graded'] = $this->convertUTCMysqlFormattedDateToHumanReadableLocalDateAndTime(date('Y-m-d H:i:s'), Auth::user()->time_zone);
        } catch (Exception $e) {

            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to save the score.  Please try again or contact us for assistance.";
        }
        return $response;

    }


//storeSubmission

    /**
     * @param Request $request
     * @param AssignmentFile $assignmentFile
     * @param Extension $extension
     * @param SubmissionFile $submissionFile
     * @param Cutup $cutup
     * @return mixed
     * @throws Exception
     */
    public
    function storeSubmissionFile(Request $request, Extension $extension, SubmissionFile $submissionFile, Cutup $cutup)
    {


        $response['type'] = 'error';
        $max_number_of_uploads_allowed = 15;//number allowed per question/assignment
        $assignment_id = $request->assignmentId;
        $question_id = $request->questionId;
        $upload_level = $request->uploadLevel;
        $user = Auth::user();
        $user_id = $user->id;

        $assignment = Assignment::find($assignment_id);


        try {
            //validator put here because I wasn't using vform so had to manually handle errors

            if ($can_upload_response = $this->canSubmitBasedOnGeneralSubmissionPolicy($user, $assignment, $assignment_id, $question_id)) {
                if ($can_upload_response['type'] === 'error') {
                    $response['message'] = $can_upload_response['message'];
                    return $response;
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


            if ($upload_count + 1 > $max_number_of_uploads_allowed) {
                $response['message'] = 'You have exceeded the number of times that you can re-upload a file submission.';
                return $response;

            }

            $validator = Validator::make($request->all(), [
                "submissionFile" => $this->fileValidator()
            ]);

            if ($validator->fails()) {
                $response['message'] = $validator->errors()->first(`submissionFile`);
                return $response;
            }


            //save locally and to S3

            $submission = $request->file("submissionFile")->store("assignments/$assignment_id", 'local');
            $submissionContents = Storage::disk('local')->get($submission);
            Storage::disk('s3')->put($submission, $submissionContents, ['StorageClass' => 'STANDARD_IA']);
            $original_filename = $request->file("submissionFile")->getClientOriginalName();


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
                    //get rid of the current ones
                    Cutup::where('user_id', $user_id)
                        ->where('assignment_id', $assignment_id)
                        ->delete();

                    $submissionFile->updateOrCreate(
                        ['user_id' => $user_id,
                            'assignment_id' => $assignment_id,
                            'type' => 'a'],
                        $submission_file_data
                    );
                    //add the cutups
                    $cutup->cutUpPdf($submission, "assignments/$assignment_id", $assignment_id, $user_id);

                    $response['message'] = 'Your PDF has been cutup into questions by page.';
                    $response['original_filename'] = $original_filename;
                    break;
                case('question'):
                    $submissionFile->updateOrCreate(
                        ['user_id' => Auth::user()->id,
                            'assignment_id' => $assignment_id,
                            'question_id' => $question_id,
                            'type' => 'q'],
                        $submission_file_data
                    );
                    $response['submission'] = basename($submission);
                    $response['original_filename'] = $original_filename;
                    $response['submission_file_url'] = $this->getTemporaryUrl($assignment_id, basename($submission));
                    $response['date_submitted'] = $this->convertUTCMysqlFormattedDateToHumanReadableLocalDateAndTime(date('Y-m-d H:i:s'), Auth::user()->time_zone);
                    break;
            }
            $response['message'] = "Your file submission has been saved.";

            $response['late_file_submission'] = $this->isLateSubmission($extension, $assignment, Carbon::now());


            if (($upload_count >= $max_number_of_uploads_allowed - 3)) {
                $response['message'] .= "  You may resubmit " . ($max_number_of_uploads_allowed - (1 + $upload_count)) . " more times.";
            }
            $response['type'] = 'success';
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to save your file submission.  Please try again or contact us for assistance.";
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
