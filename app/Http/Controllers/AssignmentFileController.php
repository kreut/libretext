<?php

namespace App\Http\Controllers;

use App\User;
use App\AssignmentFile;
use App\Assignment;
use App\Http\Requests\StoreTextFeedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

use App\Exceptions\Handler;
use \Exception;


class AssignmentFileController extends Controller
{
    public function getAssignmentFileInfoByStudent(Request $request, Assignment $assignment, AssignmentFile $assignmentFile)
    {
        $user_id = Auth::user()->id;

        $response['type'] = 'error';
        $authorized = Gate::inspect('getAssignmentFileInfoByStudent', [$assignmentFile, $assignment->id]);


        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }


        try {
            $assignmentFile = AssignmentFile::where('user_id', $user_id)
                ->where('assignment_id', $assignment->id)
                ->first();
            if (!$assignmentFile) {
                $response['type'] = 'success';
                $response['assignment_file_info'] = null;
                return $response;
            }
            $submission = $assignmentFile->submission ?? null;
            $file_feedback = $assignmentFile->file_feedback ?? null;
            $text_feedback = $assignmentFile->text_feedback ?? 'None';
            $original_filename = $assignmentFile->original_filename;
            $date_submitted = $assignmentFile->date_submitted;
            $date_graded = $assignmentFile->date_graded ?? "Not yet graded";
            $score = $assignmentFile->score ?? "N/A";
            $response['assignment_file_info'] = [
                'assignment_id' => $assignment->id,
                'submission' => $submission,
                'original_filename' => $original_filename,
                'date_submitted' => $date_submitted,
                'file_feedback' => $file_feedback,
                'text_feedback' => $text_feedback,
                'date_graded' => $date_graded,
                'score' => $score,
                'submission_url' => $submission ? $this->getTemporaryUrl($assignment->id, $submission) : null,
                'file_feedback_url' => $file_feedback ? $this->getTemporaryUrl($assignment->id, $file_feedback) : null];
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to save your assignment submission.  Please try again or contact us for assistance.";
        }
        return $response;

    }


    public function getAssignmentFilesByAssignment(Request $request, Assignment $assignment, AssignmentFile $assignmentFile)
    {


        $response['type'] = 'error';
        $authorized = Gate::inspect('viewAssignmentFilesByAssignment', [$assignmentFile, $assignment->course]);


        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }


        try {


            $assignmentFilesByUser = [];

            foreach ($assignment->assignmentFiles as $key => $assignment_file) {
                $assignment_file->needs_grading = $assignment_file->date_graded ?
                    Carbon::parse($assignment_file->date_submitted) > Carbon::parse($assignment_file->date_graded)
                    : true;
                $assignmentFilesByUser[$assignment_file->user_id] = $assignment_file;
            }
            $user_and_assignment_file_info = [];
            foreach ($assignment->course->enrolledUsers as $key => $user) {
                //get the assignment info, getting the temporary url of the first submission for viewing
                $submission = $assignmentFilesByUser[$user->id]->submission ?? null;
                $file_feedback = $assignmentFilesByUser[$user->id]->file_feedback ?? null;
                $text_feedback = $assignmentFilesByUser[$user->id]->text_feedback ?? null;
                $original_filename = $assignmentFilesByUser[$user->id]->original_filename ?? null;
                $date_submitted = $assignmentFilesByUser[$user->id]->date_submitted ?? null;
                $date_graded = $assignmentFilesByUser[$user->id]->date_graded ?? "Not yet graded";
                $score = $assignmentFilesByUser[$user->id]->score ?? "N/A";
                $all_info = ['user_id' => $user->id,
                    'name' => $user->first_name . ' ' . $user->last_name,
                    'submission' => $submission,
                    'original_filename' => $original_filename,
                    'date_submitted' => $date_submitted,
                    'file_feedback' => $file_feedback,
                    'text_feedback' => $text_feedback,
                    'date_graded' => $date_graded,
                    'score' => $score,
                    'submission_url' => ($submission && $key === 0) ? $this->getTemporaryUrl($assignment->id, $submission)
                        : null,
                    'file_feedback_url' => ($file_feedback && $key === 0) ? $this->getTemporaryUrl($assignment->id, $file_feedback)
                        : null];

                $user_and_assignment_file_info[] = $all_info;
            }

            $response['type'] = 'success';
            $response['user_and_assignment_file_info'] = $user_and_assignment_file_info;

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to retrieve the assignment files.  Please try again or contact us for assistance.";
        }
        return $response;

    }


    public function downloadAssignmentFile(Request $request, AssignmentFile $assignmentFile)
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

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function storeAssignmentFile(Request $request, AssignmentFile $assignmentFile, Assignment $assignment)
    {


        $response['type'] = 'error';
        $assignment_id = $request->assignmentId;
        $authorized = Gate::inspect('uploadAssignmentFile', [$assignmentFile, $assignment->find($assignment_id)]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            //validator put here because I wasn't using vform so had to manually handle errors

            $validator = Validator::make($request->all(), [
                'assignmentFile' => ['required', 'mimes:pdf', 'max:500000']
            ]);

            if ($validator->fails()) {
                $response['message'] = $validator->errors()->first('assignmentFile');
                return $response;
            }

            //save locally and to S3
            $submission = $request->file('assignmentFile')->store("assignments/$assignment_id", 'local');
            $submissionContents = Storage::disk('local')->get($submission);
            Storage::disk('s3')->put("$submission", $submissionContents);


            $assignmentFile->updateOrCreate(
                ['user_id' => Auth::user()->id, 'assignment_id' => $assignment_id],
                ['submission' => basename($submission),
                    'original_filename' => $request->file('assignmentFile')->getClientOriginalName(),
                    'date_submitted' => Carbon::now()]
            );
            $response['type'] = 'success';
            $response['message'] = 'Your assignment submission has been saved.';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to save your assignment submission.  Please try again or contact us for assistance.";
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
