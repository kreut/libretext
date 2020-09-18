<?php

namespace App\Http\Controllers;

use App\Score;
use App\User;
use App\AssignmentFile;
use App\SubmissionFile;
use App\Assignment;

use App\Extension;
use App\Traits\S3;
use App\Traits\DateFormatter;
use App\Http\Requests\StoreTextFeedback;
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

    public function getSubmissionFilesByAssignment(Request $request, string $type, Assignment $assignment, string $gradeView, SubmissionFile $submissionFile)
    {

        $response['type'] = 'error';

        $authorized = Gate::inspect('viewAssignmentFilesByAssignment', [$submissionFile, $assignment]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {

            switch ($type) {
                case('assignment'):
                    $user_and_submission_file_info = $submissionFile->getUserAndAssignmentFileInfo($assignment, $gradeView);

                    break;
                case('question'):
                    $user_and_submission_file_info = $submissionFile->getUserAndQuestionFileInfo($assignment, $gradeView, $assignment->course->enrolledUsers);
            }

            $response['type'] = 'success';
            $response['user_and_submission_file_info'] = $user_and_submission_file_info;

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to retrieve the file submissions for this assignment.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    public function downloadSubmissionFile(Request $request, AssignmentFile $assignmentFile, SubmissionFile $submissionFile)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('downloadAssignmentFile', [$assignmentFile, $submissionFile, $request->assignment_id, $request->submission]);


        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        return Storage::disk('s3')->download("assignments/$request->assignment_id/$request->submission");

    }

    public function getTemporaryUrlFromRequest(Request $request, AssignmentFile $assignmentFile, Assignment $assignment)
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


    public function storeTextFeedback(StoreTextFeedback $request, AssignmentFile $assignmentFile, User $user, Assignment $assignment)
    {
        $response['type'] = 'error';
        $assignment_id = $request->assignment_id;
        $question_id = $request->question_id;
        $student_user_id = $request->user_id;
        $type = $request->type;

        $authorized = Gate::inspect('storeTextFeedback', [$assignmentFile, $user->find($student_user_id), $assignment->find($assignment_id)]);


        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        if (!in_array($type, ['question', 'assignment'])) {
            $response['message'] = 'That is not a valid type of submission file.';
            return $response;
        }

        try {

            $data = $request->validated();
            $this->updateTextFeedbackOrScore($type, 'text_feedback', $data['textFeedback'], $student_user_id, $assignment_id, $question_id);


            $response['type'] = 'success';
            $response['message'] = 'Your comments have been saved.';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to save your assignment submission.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    public function storeScore(StoreScore $request, AssignmentFile $assignmentFile, User $user, Assignment $Assignment, Score $score)
    {


        $response['type'] = 'error';
        $assignment_id = $request->assignment_id;
        $question_id = $request->question_id;
        $student_user_id = $request->user_id;
        $type = $request->type;
        $assignment = $Assignment->find($assignment_id);
        $authorized = Gate::inspect('storeScore', [$assignmentFile, $user->find($student_user_id), $assignment]);


        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        if (!in_array($type, ['question', 'assignment'])) {
            $response['message'] = 'That is not a valid type of submission file.';
            return $response;
        }

        try {
            $data = $request->validated();

            DB::beginTransaction();

            $this->updateTextFeedbackOrScore($type, 'score', $data['score'], $student_user_id, $assignment_id, $question_id);
            $score->updateAssignmentScore($student_user_id, $assignment_id, $assignment->submission_files);

            DB::commit();
            $response['type'] = 'success';
            $response['message'] = 'The score has been saved.';
            $response['date_graded'] = $this->convertUTCMysqlFormattedDateToHumanReadableLocalDateAndTime(date('Y-m-d H:i:s'), Auth::user()->time_zone);
        } catch (Exception $e) {

            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to save the score.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    public function updateTextFeedbackOrScore(string $type, string $column, string $value, int $student_user_id, int $assignment_id, $question_id)
    {
        switch ($type) {
            case('assignment'):
                DB::table('submission_files')
                    ->where('user_id', $student_user_id)
                    ->where('assignment_id', $assignment_id)
                    ->where('type', 'a')
                    ->update([$column => $value, 'date_graded' => DB::raw('now()')]);
                break;
            case('question'):
                DB::table('submission_files')
                    ->where('user_id', $student_user_id)
                    ->where('assignment_id', $assignment_id)
                    ->where('question_id', $question_id)
                    ->where('type', 'q')
                    ->update([$column => $value, 'date_graded' => DB::raw('now()')]);

                break;
        }
    }
    //storeSubmission

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function storeSubmissionFile(Request $request, AssignmentFile $assignmentFile, Extension $extension, SubmissionFile $submissionFile)
    {

        $response['type'] = 'error';
        $max_number_of_uploads_allowed = 3;//number allowed per question/assignment
        $assignment_id = $request->assignmentId;

        $type = $request->type;

        $assignment = Assignment::find($assignment_id);
        $authorized = Gate::inspect('uploadAssignmentFile', [$assignmentFile, $assignment]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            //validator put here because I wasn't using vform so had to manually handle errors

            $extensions_by_assignment = $extension->getUserExtensionsByAssignment(Auth::user());

            $is_extension = isset($extensions_by_assignment[$assignment->id]);
            $due = $is_extension ? $extensions_by_assignment[$assignment->id] : $assignment->due;
            if (strtotime($due) < time()) {
                $response['message'] = 'You cannot upload a file since this assignment is past due.';
                return $response;

            }

            if (!in_array($type, ['question', 'assignment'])) {
                $response['message'] = 'That is not a valid type of submission file.';
                return $response;
            }

            if ($type === 'question') {
                $question_id = $request->questionId;
                $question_is_in_assignment = DB::table('assignment_question')->where('assignment_id', $assignment_id)
                    ->where('question_id', $question_id)
                    ->get()
                    ->isNotEmpty();
                if (!$question_is_in_assignment) {
                    $response['message'] = 'That questions is not in the assignment.';
                    return $response;
                }

            }


            switch ($type) {
                case('assignment'):
                    $latest_submission = DB::table('submission_files')
                        ->where('assignment_id', $assignment_id)
                        ->where('user_id', Auth::user()->id)
                        ->first();
                    break;
                case('question'):
                    $latest_submission = DB::table('submission_files')
                        ->where('assignment_id', $assignment_id)
                        ->where('question_id', $question_id)
                        ->where('user_id', Auth::user()->id)
                        ->select('upload_count')
                        ->first();
                    break;
            }
            $upload_count = is_null($latest_submission) ? 0 : $latest_submission->upload_count;
            if ($upload_count + 1 > $max_number_of_uploads_allowed) {
                $response['message'] = 'You have exceed the number of times that you can re-upload a submission.';
                return $response;

            }

            $validator = Validator::make($request->all(), [
                "{$type}File" => $this->fileValidator()
            ]);

            if ($validator->fails()) {
                $response['message'] = $validator->errors()->first(`{$type}File`);
                return $response;
            }


            //save locally and to S3

            $submission = $request->file("{$type}File")->store("assignments/$assignment_id", 'local');
            $submissionContents = Storage::disk('local')->get($submission);
            Storage::disk('s3')->put($submission, $submissionContents,['StorageClass' => 'STANDARD_IA']);
            $original_filename = $request->file("{$type}File")->getClientOriginalName();
            $submission_file_data = ['type' => $type[0],
                'submission' => basename($submission),
                'original_filename' => $original_filename,
                'file_feedback' => null,
                'text_feedback' => null,
                'date_graded' => null,
                'score' => null,
                'upload_count' => $upload_count + 1,
                'date_submitted' => Carbon::now()];
            switch ($type) {
                case('assignment'):
                    $submissionFile->updateOrCreate(
                        ['user_id' => Auth::user()->id,
                            'assignment_id' => $assignment_id,
                            'type' => $type[0]],
                        $submission_file_data
                    );
                    break;
                case('question'):
                    $submissionFile->updateOrCreate(
                        ['user_id' => Auth::user()->id,
                            'assignment_id' => $assignment_id,
                            'question_id' => $question_id,
                            'type' => $type[0]],
                        $submission_file_data
                    );
                    break;
            }

            $response['type'] = 'success';
            $response['message'] = "Your file submission has been saved.  You may resubmit " . ($max_number_of_uploads_allowed - (1 + $upload_count)) . " more times.";
            $response['original_filename'] = $original_filename;
            $response['date_submitted'] = $this->convertUTCMysqlFormattedDateToHumanReadableLocalDateAndTime(date('Y-m-d H:i:s'), Auth::user()->time_zone);
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to save your file submission.  Please try again or contact us for assistance.";
        }
        return $response;

    }


    public function storeFileFeedback(Request $request, AssignmentFile $assignmentFile, User $user, Assignment $assignment)
    {


        $response['type'] = 'error';
        $assignment_id = $request->assignmentId;
        $question_id = $request->questionId;
        $student_user_id = $request->userId;
        $type = $request->type;

        $authorized = Gate::inspect('uploadFileFeedback', [$assignmentFile, $user->find($student_user_id), $assignment->find($assignment_id),]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            //validator put here because I wasn't using vform so had to manually handle errors

            //wait 30 seconds between uploads
            //no more than 10 uploads per assignment
            //delete the file if there was an exception???
            if (!in_array($type, ['question', 'assignment'])) {
                $response['message'] = 'That is not a valid type of submission file.';
                return $response;
            }
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
            Storage::disk('s3')->put($fileFeedback, $feedbackContents,['StorageClass' => 'STANDARD_IA']);
            switch ($type) {
                case('assignment'):
                    DB::table('submission_files')
                        ->where('user_id', $student_user_id)
                        ->where('assignment_id', $assignment_id)
                        ->where('type', 'a')
                        ->update(['file_feedback' => basename($fileFeedback)]);
                    break;
                case('question'):
                    DB::table('submission_files')
                        ->where('user_id', $student_user_id)
                        ->where('assignment_id', $assignment_id)
                        ->where('question_id', $question_id)
                        ->where('type', 'q')
                        ->update(['file_feedback' => basename($fileFeedback)]);
                    break;
            }
            $response['type'] = 'success';
            $response['message'] = 'Your feedback file has been saved.';
            $response['file_feedback_url'] = $this->getTemporaryUrl($assignment_id, basename($fileFeedback));


        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to save your feedback file.  Please try again or contact us for assistance.";
        }
        return $response;

    }
}
