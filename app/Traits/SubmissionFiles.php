<?php


namespace App\Traits;

use App\Assignment;
use App\Helpers\Helper;
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
     * @return float|int|null
     */
    function updateScoreIfCompletedScoringType(Assignment $assignment,
                                               int        $question_id)
    {
        $score = null;
        $Score = new Score();
        if ($assignment->scoring_type === 'c') {
            $assignment_question = DB::table('assignment_question')
                ->join('questions', 'assignment_question.question_id', '=', 'questions.id')
                ->where('assignment_id', $assignment->id)
                ->where('question_id', $question_id)
                ->select('assignment_question.assignment_id',
                    'assignment_question.question_id',
                    'assignment_question.points'
                    ,'assignment_question.completion_scoring_mode',
                    'questions.technology')
                ->first();
            $score = ($assignment_question->technology === 'text')
                ? floatval($assignment_question->points)
                : $this->computeScoreForCompletion($assignment_question);
            DB::table('submission_files')
                ->where('assignment_id', $assignment->id)
                ->where('question_id', $question_id)
                ->where('user_id', Auth::user()->id)
                ->update(['score' => $score,
                    'grader_id' => $assignment->course->user_id,
                    'date_graded' => Carbon::now()]);
            $Score->updateAssignmentScore(Auth::user()->id, $assignment->id, $assignment->lms_grade_passback === 'automatic');
        }
        return $score;
    }

    function computeScoreForCompletion($assignment_question)
    {
        $completion_scoring_factor = 1;
            if ($assignment_question->completion_scoring_mode === '100% for either') {
                $auto_graded_submission_exists = DB::table('submissions')
                    ->where('user_id', Auth::user()->id)
                    ->where('assignment_id', $assignment_question->assignment_id)
                    ->where('question_id', $assignment_question->question_id)
                    ->first();
                if ($auto_graded_submission_exists) {
                    $completion_scoring_factor = 0;//don't give more points
                }
            } else {
                $percent = preg_replace('~\D~', '', $assignment_question->completion_scoring_mode);
                $completion_scoring_factor = (100 - floatval($percent)) / 100;

            }

        return floatval($assignment_question->points) * $completion_scoring_factor;
    }

    function getFormattedSubmissionFileInfo($submission_file, int $assignment_id, $helpers): array
    {

        //last_submitted is handled at the question and assignment level

        $formatted_submission_file_info = [];
        $formatted_submission_file_info['assignment_id'] = $assignment_id;
        $formatted_submission_file_info['submission'] = $submission_file['submission'] ?? null;
        $formatted_submission_file_info['original_filename'] = $submission_file['original_filename'] ?? null;
        $formatted_submission_file_info['date_submitted'] = isset($submission_file['date_submitted'])
            ? $helpers->convertUTCMysqlFormattedDateToHumanReadableLocalDateAndTime($submission_file['date_submitted'], Auth::user()->time_zone, 'M d, Y g:i a')
            : 'N/A';
        $formatted_submission_file_info['date_graded'] = ($submission_file['date_graded'])
            ? $helpers->convertUTCMysqlFormattedDateToHumanReadableLocalDateAndTime($submission_file['date_graded'], Auth::user()->time_zone, 'M d, Y g:i a')
            : 'N/A';
        $formatted_submission_file_info['file_feedback_exists'] = isset($submission_file['file_feedback']);
        $formatted_submission_file_info['file_feedback'] = $submission_file['file_feedback'] ?? null;
        $formatted_submission_file_info['text_feedback'] = $submission_file['text_feedback'] ?? 'N/A';
        $formatted_submission_file_info['submission_file_score'] = Helper::removeZerosAfterDecimal($submission_file['file_submission_score']) ?? 'N/A';
        $formatted_submission_file_info['temporary_url'] = null;
        $formatted_submission_file_info['file_feedback_url'] = null;
        $formatted_submission_file_info['late_penalty_percent'] = $submission_file['late_penalty_percent'] ?? null;
        $formatted_submission_file_info['applied_late_penalty'] = $submission_file['applied_late_penalty'] ?? null;
        if ($submission_file) {
            $formatted_submission_file_info['temporary_url'] = $helpers->getTemporaryUrl($assignment_id, $submission_file['submission']);
            if ($submission_file['file_feedback']) {
                $formatted_submission_file_info['file_feedback_url'] = $helpers->getTemporaryUrl($assignment_id, $submission_file['file_feedback']);
            }
        }
        return $formatted_submission_file_info;
    }
}
