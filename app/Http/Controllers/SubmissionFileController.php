<?php

namespace App\Http\Controllers;

use App\User;
use App\AssignmentFile;
use App\SubmissionFile;
use App\Assignment;
use App\Extension;
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




    public function downloadSubmissionFile(Request $request, AssignmentFile $assignmentFile)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('downloadAssignmentFile', [$assignmentFile, $request->assignment_id, $request->submission]);


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
            $response['message'] = "We were not able to save your assignment submission.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    public function getTemporaryUrl($assignment_id, $file)
    {
        return Storage::disk('s3')->temporaryUrl("assignments/$assignment_id/$file", now()->addMinutes(5));
    }


    public function storeTextFeedback(StoreTextFeedback $request, AssignmentFile $assignmentFile, User $user, Assignment $assignment)
    {
        $response['type'] = 'error';
        $assignment_id = $request->assignment_id;
        $student_user_id = $request->user_id;
        $authorized = Gate::inspect('storeTextFeedback', [$assignmentFile, $user->find($student_user_id), $assignment->find($assignment_id)]);


        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {

            $data = $request->validated();
            DB::table('assignment_files')
                ->where('user_id', $student_user_id)
                ->where('assignment_id', $assignment_id)
                ->update(['text_feedback' => $data['textFeedback']]);

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

            $validator = Validator::make($request->all(), [
                "{$type}File" => ['required', 'mimes:pdf', 'max:500000']
            ]);

            if ($validator->fails()) {
                $response['message'] = $validator->errors()->first('assignmentFile');
                return $response;
            }


            //save locally and to S3

            $submission_file = $request->file("{$type}File")->store("assignments/$assignment_id", 'local');
            $submissionContents = Storage::disk('local')->get($submission_file);
            Storage::disk('s3')->put($submission_file, $submissionContents);
            switch ($type) {
                case('assignment'):
                    $submissionFile->updateOrCreate(
                        ['user_id' => Auth::user()->id, 'assignment_id' => $assignment_id],
                        ['type' => 'a',
                        'submission_file' => basename($submission_file),
                        'original_filename' => $request->file('assignmentFile')->getClientOriginalName(),
                        'date_submitted' => Carbon::now()]
                    );
                    break;
                case('question'):
                    $questionFile->updateOrCreate(
                        ['user_id' => Auth::user()->id, 'assignment_id' => $assignment_id],
                        ['submission' => basename($submission),
                            'original_filename' => $request->file('assignmentFile')->getClientOriginalName(),
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
        $student_user_id = $request->userId;

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
            DB::table('assignment_files')
                ->where('user_id', $student_user_id)
                ->where('assignment_id', $assignment_id)
                ->update(['file_feedback' => basename($fileFeedback)]);

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
