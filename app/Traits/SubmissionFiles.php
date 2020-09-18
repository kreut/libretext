<?php


namespace App\Traits;

use App\SubmissionFile;
use Illuminate\Support\Facades\Auth;

trait SubmissionFiles
{


    function getFormattedSubmissionFileInfo($submission_file, int $assignment_id, $helpers)
    {


        //last_submitted is handled at the question and assignment level
        $formatted_submission_file_info = [];
        $formatted_submission_file_info['assignment_id'] = $assignment_id;
        $formatted_submission_file_info['submission'] = $submission_file['submission'] ?? null;
        $formatted_submission_file_info['original_filename'] = $submission_file['original_filename'] ?? null;
        $formatted_submission_file_info['date_submitted'] = isset($submission_file['date_submitted'])
            ? $helpers->convertUTCMysqlFormattedDateToHumanReadableLocalDateAndTime($submission_file['date_submitted'], Auth::user()->time_zone)
            : 'N/A';
        $formatted_submission_file_info['date_graded'] = ($submission_file['date_graded'])
            ? $helpers->convertUTCMysqlFormattedDateToHumanReadableLocalDateAndTime($submission_file['date_graded'], Auth::user()->time_zone)
            : 'N/A';
        $formatted_submission_file_info['file_feedback_exists'] = isset($submission_file['file_feedback']);
        $formatted_submission_file_info['file_feedback'] = $submission_file['file_feedback'] ?? null;
        $formatted_submission_file_info['text_feedback'] = $submission_file['text_feedback'] ?? 'N/A';
        $formatted_submission_file_info['submission_file_score'] = $submission_file['score'] ?? 'N/A';
        $formatted_submission_file_info['temporary_url'] = null;
        $formatted_submission_file_info['file_feedback_url'] = null;
            if ($submission_file) {
                $formatted_submission_file_info['temporary_url'] = $helpers->getTemporaryUrl($assignment_id, $submission_file['submission']);
                if ($submission_file['file_feedback']) {
                    $formatted_submission_file_info['file_feedback_url'] = $helpers->getTemporaryUrl($assignment_id, $submission_file['file_feedback']);
                }
            }
        return   $formatted_submission_file_info;
    }
}
