<?php

namespace App\Http\Controllers;

use App\User;
use App\SubmissionFile;
use App\Assignment;
use App\Extension;
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

    //getSubmissionFileController?
    public function getAssignmentFileInfoByStudent(Request $request, Assignment $assignment, SubmissionFile $submissionFile)
    {
        $user_id = Auth::user()->id;

        $response['type'] = 'error';
        $authorized = Gate::inspect('getAssignmentFileInfoByStudent', [$assignmentFile, $assignment->id]);


        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }


        try {
            $submissionFile = SubmissionFile::where('user_id', $user_id)
                ->where('assignment_id', $assignment->id)
                ->where('type','a')
                ->first();
            if (!$submissionFile) {
                $response['type'] = 'success';
                $response['assignment_file_info'] = null;
                return $response;
            }
            $submission = $submissionFile->submission ?? null;
            $file_feedback = $submissionFile->file_feedback ?? null;
            $text_feedback = $submissionFile->text_feedback ?? 'None';
            $original_filename = $submissionFile->original_filename;
            $date_submitted = $submissionFile->date_submitted;
            $date_graded = $submissionFile->date_graded ?? "Not yet graded";
            $score = $submissionFile->score ?? "N/A";
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

//getSubmissionFilesByAssignment
    public function getAssignmentFilesByAssignment(Request $request, Assignment $assignment, SubmissionFile $submissionFile)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('viewAssignmentFilesByAssignment', [$submissionFile, $assignment]);


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


    public function getTemporaryUrl($assignment_id, $file)
    {
        return Storage::disk('s3')->temporaryUrl("assignments/$assignment_id/$file", now()->addMinutes(5));
    }


}
