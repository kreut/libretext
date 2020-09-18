<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Traits\S3;
use App\Traits\DateFormatter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class SubmissionFile extends Model
{
    use S3;
    use DateFormatter;

    protected $guarded = [];

    public function getAllInfo(User $user, Assignment $assignment, $key, $submission, $question_id, $original_filename, $date_submitted, $file_feedback, $text_feedback, $date_graded, $score)
    {
        return ['user_id' => $user->id,
            'name' => $user->first_name . ' ' . $user->last_name,
            'submission' => $submission,
            'question_id' => $question_id,
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

    public function getUserAndAssignmentFileInfo(Assignment $assignment, string $grade_view)
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
            $question_id = null;//at the assignment level
            $file_feedback = $assignmentFilesByUser[$user->id]->file_feedback ?? null;
            $text_feedback = $assignmentFilesByUser[$user->id]->text_feedback ?? null;
            $original_filename = $assignmentFilesByUser[$user->id]->original_filename ?? null;
            $date_submitted = isset($assignmentFilesByUser[$user->id]->date_submitted)
                ? $this->convertUTCMysqlFormattedDateToHumanReadableLocalDateAndTime($assignmentFilesByUser[$user->id]->date_submitted, Auth::user()->time_zone)
                : null;
            $date_graded = isset($assignmentFilesByUser[$user->id]->date_graded)
                ? $this->convertUTCMysqlFormattedDateToHumanReadableLocalDateAndTime($assignmentFilesByUser[$user->id]->date_graded, Auth::user()->time_zone)
                : "Not yet graded";
            $score = $assignmentFilesByUser[$user->id]->score ?? "N/A";
            $all_info = $this->getAllInfo($user, $assignment, $key, $submission, $question_id, $original_filename, $date_submitted, $file_feedback, $text_feedback, $date_graded, $score);
            if ($this->inGradeView($all_info, $grade_view)) {
                $user_and_submission_file_info[] = $all_info;

            }
        }
        //dd($user_and_submission_file_info);
        return [$user_and_submission_file_info];//create array so that it works like questions below
    }

    public function inGradeView($file, $grade_view)
    {
        $in_grade_view = false;
        switch ($grade_view) {
            case('allStudents'):
                $in_grade_view = true;
                break;
            case('ungradedSubmissions'):
                $in_grade_view = $file['submission'] && ($file['date_graded'] === 'Not yet graded');
                break;
            case('gradedSubmissions'):
                $in_grade_view = $file['submission'] && ($file['date_graded'] !== 'Not yet graded');
                break;
            case('studentsWithoutSubmissions'):
                $in_grade_view = !$file['submission'];
                break;

        }
        return $in_grade_view;


    }

    public function getUserAndQuestionFileInfo(Assignment $assignment, string $grade_view, $users)
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
            foreach ($users as $key => $user) {

                //get the assignment info, getting the temporary url of the first submission for viewing
                $submission = $questionFilesByUser[$question->question_id][$user->id]->submission ?? null;
                $question_id = $question->question_id;
                $file_feedback = $questionFilesByUser[$question->question_id][$user->id]->file_feedback ?? null;
                $text_feedback = $questionFilesByUser[$question->question_id][$user->id]->text_feedback ?? null;
                $original_filename = $questionFilesByUser[$question->question_id][$user->id]->original_filename ?? null;
                $date_submitted = isset($questionFilesByUser[$question->question_id][$user->id]->date_submitted)
                    ? $this->convertUTCMysqlFormattedDateToHumanReadableLocalDateAndTime($questionFilesByUser[$question->question_id][$user->id]->date_submitted, Auth::user()->time_zone)
                    : null;
                $date_graded = isset($questionFilesByUser[$question->question_id][$user->id]->date_graded)
                    ? $this->convertUTCMysqlFormattedDateToHumanReadableLocalDateAndTime($questionFilesByUser[$question->question_id][$user->id]->date_graded, Auth::user()->timne_zone)
                    : null;
                $score = $questionFilesByUser[$question->question_id][$user->id]->score ?? "N/A";
                $all_info = $this->getAllInfo($user, $assignment, $key, $submission, $question_id, $original_filename, $date_submitted, $file_feedback, $text_feedback, $date_graded, $score);
                if ($this->inGradeView($all_info, $grade_view)) {
                    $user_and_submission_file_info[$question->question_id][$key] = $all_info;
                }
            }
        }
        //re-key so that paginate can handle it
        return $this->reKeyUserAndSubmissionFileInfo($user_and_submission_file_info);
    }

    public function reKeyUserAndSubmissionFileInfo(array $user_and_submission_file_info)
    {
        $re_keyed_by_question_and_user_info = [];
        if ($user_and_submission_file_info) {

            $re_keyed_by_user_info = [];
            foreach ($user_and_submission_file_info as $question => $students) {
                $re_keyed_by_user_info[$question] = [];
                foreach ($students as $student) {
                    $re_keyed_by_user_info[$question][] = $student;
                }
            }
            $re_keyed_by_question_and_user_info = [];
            foreach ($re_keyed_by_user_info as $value) {
                array_push($re_keyed_by_question_and_user_info, $value);
            }
        }
        return $re_keyed_by_question_and_user_info;
    }
}
