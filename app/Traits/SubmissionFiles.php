<?php


namespace App\Traits;

use App\Assignment;
use App\Score;
use App\Submission;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

trait SubmissionFiles
{
    /**
     * @param Assignment $assignment
     * @param int $question_id
     * @return void
     */
    function updateScoreIfCompletedScoringType(Assignment $assignment,
                                               int        $question_id)
    {
        $Score = new Score();
        if ($assignment->scoring_type === 'c') {
            $assignment_question = DB::table('assignment_question')
                ->join('questions', 'assignment_question.question_id', '=', 'questions.id')
                ->where('assignment_id', $assignment->id)
                ->where('question_id', $question_id)
                ->select('assignment_question.points', 'questions.technology')
                ->first();
            $score = ($assignment_question->technology === 'text')
                ? floatval($assignment_question->points)
                : .5 * floatval($assignment_question->points);
            DB::table('submission_files')
                ->where('assignment_id', $assignment->id)
                ->where('question_id', $question_id)
                ->where('user_id', Auth::user()->id)
                ->update(['score' => $score,
                    'grader_id' => $assignment->course->user_id,
                    'date_graded' => Carbon::now()]);
            $Score->updateAssignmentScore(Auth::user()->id, $assignment->id);
        }
    }

    function getFormattedSubmissionFileInfo($submission_file, int $assignment_id, $helpers): array
    {


        //last_submitted is handled at the question and assignment level
        $formatted_submission_file_info = [];
        $formatted_submission_file_info['assignment_id'] = $assignment_id;
        $formatted_submission_file_info['submission'] = $submission_file['submission'] ?? null;
        $formatted_submission_file_info['original_filename'] = $submission_file['original_filename'] ?? null;
        $formatted_submission_file_info['date_submitted'] = isset($submission_file['date_submitted'])
            ? $helpers->convertUTCMysqlFormattedDateToHumanReadableLocalDateAndTime($submission_file['date_submitted'], Auth::user()->time_zone, 'M d, Y g:i:s a')
            : 'N/A';
        $formatted_submission_file_info['date_graded'] = ($submission_file['date_graded'])
            ? $helpers->convertUTCMysqlFormattedDateToHumanReadableLocalDateAndTime($submission_file['date_graded'], Auth::user()->time_zone, 'M d, Y g:i:s a')
            : 'N/A';
        $formatted_submission_file_info['file_feedback_exists'] = isset($submission_file['file_feedback']);
        $formatted_submission_file_info['file_feedback'] = $submission_file['file_feedback'] ?? null;
        $formatted_submission_file_info['text_feedback'] = $submission_file['text_feedback'] ?? 'N/A';
        $formatted_submission_file_info['submission_file_score'] = $submission_file['file_submission_score'] ?? 'N/A';
        $formatted_submission_file_info['temporary_url'] = null;
        $formatted_submission_file_info['file_feedback_url'] = null;
        if ($submission_file) {
            $formatted_submission_file_info['temporary_url'] = $helpers->getTemporaryUrl($assignment_id, $submission_file['submission']);
            if ($submission_file['file_feedback']) {
                $formatted_submission_file_info['file_feedback_url'] = $helpers->getTemporaryUrl($assignment_id, $submission_file['file_feedback']);
            }
        }
        return $formatted_submission_file_info;
    }
}
