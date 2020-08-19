<?php

namespace App\Http\Controllers;

use App\User;
use App\AssignmentFile;
use App\Assignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class AssignmentFileController extends Controller
{

    public function getAssignmentFilesByAssignment(Request $request, Assignment $assignment)
    {
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
            $original_filename = $assignmentFilesByUser[$user->id]->original_filename ?? null;
            $date_submitted = $assignmentFilesByUser[$user->id]->date_submitted ?? null;
            $feedback_file = $assignmentFilesByUser[$user->id]->feedback_file ?? null;
            $date_graded = $assignmentFilesByUser[$user->id]->date_graded ?? "Not yet graded";
            $score = $assignmentFilesByUser[$user->id]->score ?? "N/A";
            $all_info = ['user_id' => $user->id,
                'name' => $user->first_name . ' ' . $user->last_name,
                'submission' => $submission,
                'original_filename' => $original_filename,
                'date_submitted' => $date_submitted,
                'feedback_file' => $feedback_file,
                'date_graded' => $date_graded,
                'score' => $score,
                'url' => ($submission && $key === 0) ? $this->getAssignmentSubmissionTemporaryUrl($assignment->id, $submission)
                    : null];

            $user_and_assignment_file_info[] = $all_info;
        }

        return $user_and_assignment_file_info;
    }

    public function getTemporaryUrl(Request $request)
    {
        return $this->getAssignmentSubmissionTemporaryUrl($request->assignment_id, $request->submission);

    }

    public function downloadAssignmentFile(Request $request)
    {
        return Storage::disk('s3')->download("assignments/$request->assignment_id/$request->submission");

    }

    public function getAssignmentSubmissionTemporaryUrl($assignment_id, $submission, $time = 5)
    {
        return Storage::disk('s3')->temporaryUrl("assignments/$assignment_id/$submission", now()->addMinutes($time));
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

            //wait 30 seconds between uploads
            //no more than 10 uploads per assignment
            //delete the file if there was an exception???

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
            Storage::disk('s3')->put("assignments/$assignment_id/$submission", $submissionContents);

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


    public function storeFeedbackFile(Request $request, AssignmentFile $assignmentFile, User $user, Assignment $assignment)
    {

        $response['type'] = 'error';
        $assignment_id = $request->assignmentId;
        $student_user_id = $request->userId;

        $authorized = Gate::inspect('uploadfeedbackFile', [$assignmentFile, $user->find($student_user_id), $assignment->find($assignment_id),]);
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
                'feedbackFile' => ['required', 'mimes:pdf', 'max:500000']
            ]);

            if ($validator->fails()) {
                $response['message'] = $validator->errors()->first('feedbackFile');
                return $response;
            }

            //save locally and to S3
            $feedbackFile = $request->file('feedbackFile')->store("feedbacks/$assignment_id", 'local');
            $feedbackContents = Storage::disk('local')->get($feedbackFile);
            Storage::disk('s3')->put("feedbacks/$assignment_id/$feedbackFile", $feedbackContents);
            DB::table('assignment_files')
                ->where('user_id', $student_user_id)
                ->where('assignment_id', $assignment_id)
                ->update(['file_feedback' => basename($feedbackFile)]);

            $response['type'] = 'success';
            $response['message'] = 'Your feedback file has been saved.';

            /*
             * Start:
             * 1. make sure the insertorupdate thing works
             * 2. Show the file and add to the vue object on return
             * 2. Add comments
             * Move the button over
             * Progress bar or ladda to disable?
             * 3. Add grade
             * 4. Student view comments
             * 5. Show All or just ungraded
             * 6. Student should be able to see the results
             * 7. figure out the pdf viewer
             */


        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to save your feedback file.  Please try again or contact us for assistance.";
        }
        return $response;

    }
}
