<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Traits\S3;
use Illuminate\Support\Facades\DB;

class SubmissionFile extends Model
{
    use S3;

    protected $guarded = [];
    public function getAllInfo(User $user, Assignment $assignment, $key, $submission, $original_filename, $date_submitted, $file_feedback, $text_feedback, $date_graded, $score){
        return ['user_id' => $user->id,
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

    }

    public function getUserAndAssignmentFileInfo(Assignment $assignment)
    {

        foreach ($assignment->assignmentFileSubmissions as $key => $assignment_file) {
            $assignment_file->needs_grading = $assignment_file->date_graded ?
                Carbon::parse($assignment_file->date_submitted) > Carbon::parse($assignment_file->date_graded)
                : true;
            $assignmentFilesByUser[$assignment_file->user_id] = $assignment_file;
        }

        $user_and_submission_file_info = [];

        foreach ($assignment->course->enrolledUsers as $key => $user) {
            //get the assignment info, getting the temporary url of the first submission for viewing
            $submission = $assignmentFilesByUser[$user->id]->submission ?? null;
            $file_feedback = $assignmentFilesByUser[$user->id]->file_feedback ?? null;
            $text_feedback = $assignmentFilesByUser[$user->id]->text_feedback ?? null;
            $original_filename = $assignmentFilesByUser[$user->id]->original_filename ?? null;
            $date_submitted = $assignmentFilesByUser[$user->id]->date_submitted ?? null;
            $date_graded = $assignmentFilesByUser[$user->id]->date_graded ?? "Not yet graded";
            $score = $assignmentFilesByUser[$user->id]->score ?? "N/A";
            $all_info = $this->getAllInfo($user, $assignment, $key, $submission, $original_filename, $date_submitted, $file_feedback, $text_feedback, $date_graded, $score);

            $user_and_submission_file_info[] = $all_info;
        }
        return $user_and_submission_file_info;
    }

    public function getUserAndQuestionFileInfo(Assignment $assignment)
    {


        foreach ($assignment->questionFileSubmissions as $key => $question_file) {
            $question_file->needs_grading = $question_file->date_graded ?
                Carbon::parse($question_file->date_submitted) > Carbon::parse($question_file->date_graded)
                : true;
            $questionFilesByUser[$question_file->question_id][$question_file->user_id] = $question_file;
        }
        $user_and_submission_file_info = [];

        $assignment_questions_where_student_can_upload_file = DB::table('assignment_question')
            ->where('assignment_id', $assignment->id)
            ->where('question_files', 1)
            ->get();

        foreach ($assignment_questions_where_student_can_upload_file as $question) {
            foreach ($assignment->course->enrolledUsers as $key => $user) {
                //get the assignment info, getting the temporary url of the first submission for viewing
                $submission = $questionFilesByUser[$question->question_id][$user->id]->submission ?? null;
                $file_feedback = $questionFilesByUser[$question->question_id][$user->id]->file_feedback ?? null;
                $text_feedback = $questionFilesByUser[$question->question_id][$user->id]->text_feedback ?? null;
                $original_filename = $questionFilesByUser[$question->question_id][$user->id]->original_filename ?? null;
                $date_submitted = $questionFilesByUser[$question->question_id][$user->id]->date_submitted ?? null;
                $date_graded = $questionFilesByUser[$question->question_id][$user->id]->date_graded ?? "Not yet graded";
                $score = $questionFilesByUser[$question->question_id][$user->id]->score ?? "N/A";
                $all_info = $this->getAllInfo($user, $assignment, $key, $submission, $original_filename, $date_submitted, $file_feedback, $text_feedback, $date_graded, $score);
                $user_and_submission_file_info[$question->question_id][$user->id] = $all_info;
            }
        }
        return $user_and_submission_file_info;
    }




}
