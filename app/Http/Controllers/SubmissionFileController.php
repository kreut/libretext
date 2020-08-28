<?php

namespace App\Http\Controllers;

use App\User;
use App\AssignmentFile;
use App\SubmissionFile;
use App\Assignment;
use App\Extension;
use App\Traits\S3;
use App\Http\Requests\StoreTextFeedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

use App\Exceptions\Handler;
use \Exception;


class SubmissionFileController extends Controller
{

    use S3;
    public function getSubmissionFilesByAssignment(Request $request, string $type, Assignment $assignment, SubmissionFile $submissionFile)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('viewAssignmentFilesByAssignment', [$submissionFile, $assignment]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $assignmentFilesByUser = [];

            switch ($type){
                case('assignment'):
                    $user_and_submission_file_info = $submissionFile->getUserAndAssignmentFileInfo($assignment);
                    break;
                case('question'):

                    $user_and_submission_file_info = $submissionFile->getUserAndQuestionFileInfo($assignment);
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
            switch ($type) {
                case('assignment'):
                    DB::table('submission_files')
                        ->where('user_id', $student_user_id)
                        ->where('assignment_id', $assignment_id)
                        ->where('type', 'a')
                        ->update(['text_feedback' => $data['textFeedback']]);
                    break;
                case('question'):
                    DB::table('submission_files')
                        ->where('user_id', $student_user_id)
                        ->where('assignment_id', $assignment_id)
                        ->where('question_id', $question_id)
                        ->where('type', 'q')
                        ->update(['text_feedback' => $data['textFeedback']]);

                    break;
            }

            $response['type'] = 'success';
            $response['message'] = 'Your comments have been saved.';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to save your assignment submission.  Please try again or contact us for assistance.";
        }
        return $response;

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

            $validator = Validator::make($request->all(), [
                "{$type}File" => ['required', 'mimes:pdf', 'max:500000']
            ]);

            if ($validator->fails()) {
                $response['message'] = $validator->errors()->first(`{$type}File`);
                return $response;
            }


            //save locally and to S3

            $submission = $request->file("{$type}File")->store("assignments/$assignment_id", 'local');
            $submissionContents = Storage::disk('local')->get($submission);
            Storage::disk('s3')->put($submission, $submissionContents);
            switch ($type) {
                case('assignment'):
                    $submissionFile->updateOrCreate(
                        ['user_id' => Auth::user()->id, 'assignment_id' => $assignment_id],
                        ['type' => 'a',
                            'submission' => basename($submission),
                            'original_filename' => $request->file('assignmentFile')->getClientOriginalName(),
                            'date_submitted' => Carbon::now()]
                    );
                    break;
                case('question'):
                    $submissionFile->updateOrCreate(
                        ['user_id' => Auth::user()->id,
                            'assignment_id' => $assignment_id,
                            'question_id' => $question_id],
                        ['type' => 'q',
                            'submission' => basename($submission),
                            'original_filename' => $request->file('questionFile')->getClientOriginalName(),
                            'date_submitted' => Carbon::now()]
                    );
                    break;


            }

            $response['type'] = 'success';
            $response['message'] = 'Your file submission has been saved.';
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
                'fileFeedback' => ['required', 'mimes:pdf', 'max:500000']
            ]);

            if ($validator->fails()) {
                $response['message'] = $validator->errors()->first('fileFeedback');
                return $response;
            }

            //save locally and to S3
            $fileFeedback = $request->file('fileFeedback')->store("assignments/$assignment_id", 'local');
            $feedbackContents = Storage::disk('local')->get($fileFeedback);
            Storage::disk('s3')->put("$fileFeedback", $feedbackContents);
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
