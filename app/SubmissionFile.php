<?php

namespace App;

use App\Exceptions\Handler;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Traits\S3;
use App\Traits\GeneralSubmissionPolicy;
use App\Traits\LatePolicy;

use App\Traits\DateFormatter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class SubmissionFile extends Model
{
    use S3;
    use DateFormatter;
    use GeneralSubmissionPolicy;
    use LatePolicy;

    protected $guarded = [];


    public
    function getAllInfo(User $user, Assignment $assignment, $solution, $submission, $question_id, $original_filename, $date_submitted, $file_feedback, $text_feedback, $date_graded, $file_submission_score, $question_submission_score = null)
    {
        $file_feedback_type = null;
        $file_feedback_url = null;
        if ($file_feedback) {
            $file_feedback_type = (pathinfo($file_feedback, PATHINFO_EXTENSION) === 'mpga') ? 'audio' : 'q';
        }
        return ['user_id' => $user->id,
            'name' => $user->first_name . ' ' . $user->last_name,
            'submission' => $submission,
            'question_id' => $question_id,
            'original_filename' => $original_filename,
            'date_submitted' => $date_submitted,
            'file_feedback' => $file_feedback,
            'text_feedback' => $text_feedback,
            'date_graded' => $date_graded,
            'question_submission_score' => $question_submission_score,
            'solution' => $solution,
            'file_feedback_type' => $file_feedback_type,
            'file_submission_score' => $file_submission_score,
            'submission_url' => $submission ? $this->getTemporaryUrl($assignment->id, $submission) : null,
            'file_feedback_url' => $file_feedback ? $this->getTemporaryUrl($assignment->id, $file_feedback) : null];

    }

    public
    function getUserAndAssignmentFileInfo(Assignment $assignment, string $grade_view)
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

            $solution = false;  //TODO: just done at the question level

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
            $all_info = $this->getAllInfo($user, $assignment, $solution, $submission, $question_id, $original_filename, $date_submitted, $file_feedback, $text_feedback, $date_graded, $score);
            if ($this->inGradeView($all_info, $grade_view)) {
                $user_and_submission_file_info[] = $all_info;

            }
        }
        //dd($user_and_submission_file_info);
        return [$user_and_submission_file_info];//create array so that it works like questions below
    }

    public
    function inGradeView($file, $grade_view)
    {
        $in_grade_view = false;
        switch ($grade_view) {
            case('allStudents'):
                $in_grade_view = true;
                break;
            case('ungradedSubmissions'):
                $in_grade_view = $file['submission'] && ($file['date_graded'] === NUll);
                break;
            case('gradedSubmissions'):
                $in_grade_view = $file['submission'] && ($file['date_graded'] !== NULL);
                break;
            case('studentsWithoutSubmissions'):
                $in_grade_view = !$file['submission'];
                break;

        }
        return $in_grade_view;


    }

    public
    function getUserAndQuestionFileInfo(Assignment $assignment, string $grade_view, $users)
    {

        ///what if null?
         $extensions = [];
        foreach ($assignment->extensions as $extension){
            $extensions[$extension->user_id] = $extension->extension;
        }
        foreach ($assignment->submissions as $submission) {
            $question_submission_scores[$submission->question_id][$submission->user_id] = $submission->score;
        }
        foreach ($assignment->questionFileSubmissions() as $key => $question_file) {
            $question_file->needs_grading = $question_file->date_graded ?
                Carbon::parse($question_file->date_submitted) > Carbon::parse($question_file->date_graded)
                : true;
            $questionFilesByUser[$question_file->question_id][$question_file->user_id] = $question_file;
        }
        $user_and_submission_file_info = [];

        $assignment_questions_where_student_can_upload_file = DB::table('assignment_question')
            ->where('assignment_id', $assignment->id)
            ->whereIn('open_ended_submission_type', ['file','text','audio'])
            ->get();

        $question_ids = [];

        if ($assignment->questions->isNotEmpty()) {
            foreach ($assignment->questions as $question) {
                $question_ids[] = $question->id;
            }
        }

        $solutions = DB::table('solutions')
            ->whereIn('question_id', $question_ids)
            ->where('user_id', $assignment->course->user_id)
            ->get();

        $solutions_by_question_id = [];
        if ($solutions->isNotEmpty()) {
            foreach ($solutions as $solution) {
                $solutions_by_question_id[$solution->question_id] = $solution->original_filename;
            }
        }

        $points = [];
        foreach ($assignment_questions_where_student_can_upload_file as $question) {

            foreach ($users as $key => $user) {
                $points[$question->question_id][$user->id] = $question->points;
                //get the assignment info, getting the temporary url of the first submission for viewing
                $submission = $questionFilesByUser[$question->question_id][$user->id]->submission ?? null;
                $open_ended_submission_type = $question->open_ended_submission_type;
                $question_id = $question->question_id;
                $file_feedback = $questionFilesByUser[$question->question_id][$user->id]->file_feedback ?? null;
                $text_feedback = $questionFilesByUser[$question->question_id][$user->id]->text_feedback ?? null;
                $original_filename = $questionFilesByUser[$question->question_id][$user->id]->original_filename ?? null;
                $extension = isset($extensions[$user->user_id] ) ? $extensions[$user->user_id] : null;
                if ($submission && in_array($assignment->late_policy, [ 'marked late', 'deduction'])){
                    $late_file_submission =  $this->isLateSubmissionGivenExtensionForMarkedLatePolicy($extension,  $assignment->due,  $questionFilesByUser[$question->question_id][$user->id]->date_submitted);
                }

                $solution = $solutions_by_question_id[$question->question_id] ?? false;


                /**TODO: clean up the next bit of code!!!!
                 * Though it works, it's a hack.  Basically, if a single user is getting their file info, there's no need to do the
                 * formatting of the date because it's done already.
                 * However, if it's for the class it has to be done.
                 * **/
                $grader_id = null;//just for the individual level in case of complaints;
                $grader_name = null;
                if (count($users) === 1) {
                    $date_submitted = $questionFilesByUser[$question->question_id][$user->id]->date_submitted ?? null;
                    $date_graded = $questionFilesByUser[$question->question_id][$user->id]->date_graded ?? null;
                    if (isset($questionFilesByUser[$question->question_id][$user->id])) {
                        $grader_id = $questionFilesByUser[$question->question_id][$user->id]->grader_id;
                    }

                } else {
                    $date_submitted = isset($questionFilesByUser[$question->question_id][$user->id]->date_submitted)
                        ? $this->convertUTCMysqlFormattedDateToHumanReadableLocalDateAndTime($questionFilesByUser[$question->question_id][$user->id]->date_submitted, Auth::user()->time_zone)
                        : null;
                    $date_graded = isset($questionFilesByUser[$question->question_id][$user->id]->date_graded)
                        ? $this->convertUTCMysqlFormattedDateToHumanReadableLocalDateAndTime($questionFilesByUser[$question->question_id][$user->id]->date_graded, Auth::user()->time_zone)
                        : null;
                    $grader_name = $questionFilesByUser[$question->question_id][$user->id]->grader_name ?? null;
                }

                $file_submission_score = $questionFilesByUser[$question->question_id][$user->id]->score ?? "N/A";
                $question_submission_score = $question_submission_scores[$question->question_id][$user->id] ?? 0;
                $all_info = $this->getAllInfo($user, $assignment, $solution, $submission, $question_id, $original_filename, $date_submitted, $file_feedback, $text_feedback, $date_graded, $file_submission_score, $question_submission_score);
                $all_info['grader_id'] = $grader_id;
                $all_info['open_ended_submission_type'] = $open_ended_submission_type;
                $all_info['grader_name'] = $grader_name;
                $all_info['late_file_submission'] = $late_file_submission ?? false;
                $all_info['order'] = $question->order;
                // $all_info['grader_name'] = $grader_name;
                if ($this->inGradeView($all_info, $grade_view)) {
                    $user_and_submission_file_info[$question->question_id][$key] = $all_info;
                }
            }
        }
        $reKeyedUserAndSubmissionFileInfo = $this->reKeyUserAndSubmissionFileInfo($user_and_submission_file_info);

        foreach ($reKeyedUserAndSubmissionFileInfo as $question => $user_question) {
            foreach ($user_question as $key => $info) {
                $reKeyedUserAndSubmissionFileInfo[$question][$key]['points'] = $points[$info['question_id']][$info['user_id']];
            }
        }
        $sortedUserAndSubmissionFileInfo = [];
        foreach  ($reKeyedUserAndSubmissionFileInfo as $question => $users){
            usort($users, function($a, $b) {
                return $a['name'] <=> $b['name'];
            });
            $sortedUserAndSubmissionFileInfo[$question] = $users;

        }

        return $sortedUserAndSubmissionFileInfo;


    }

    public
    function reKeyUserAndSubmissionFileInfo(array $user_and_submission_file_info)
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
