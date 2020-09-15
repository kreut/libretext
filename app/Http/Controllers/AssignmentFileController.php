<?php

namespace App\Http\Controllers;


use App\AssignmentFile;
use App\SubmissionFile;
use App\Assignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

use App\Traits\DateFormatter;
use App\Traits\SubmissionFiles;

use App\Exceptions\Handler;
use \Exception;


class AssignmentFileController extends Controller
{

    use DateFormatter;
    use SubmissionFiles;
    public function getAssignmentFileInfoByStudent(Request $request, Assignment $assignment, SubmissionFile $submissionFile, AssignmentFile $assignmentFile)
    {

        $user_id = Auth::user()->id;

        $response['type'] = 'error';
        $authorized = Gate::inspect('getAssignmentFileInfoByStudent', [$assignmentFile, $submissionFile, $assignment->id]);


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

            $response['assignment_file_info']  = $this->getFormattedSubmissionFileInfo($submissionFile, $assignment->id, $this);


            /*
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
                'file_feedback_url' => $file_feedback ? $this->getTemporaryUrl($assignment->id, $file_feedback) : null];*/
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to get the file information.  Please try again or contact us for assistance.";
        }
        return $response;

    }

//getSubmissionFilesByAssignment



    public function getTemporaryUrl($assignment_id, $file)
    {
        return Storage::disk('s3')->temporaryUrl("assignments/$assignment_id/$file", now()->addMinutes(5));
    }


}
