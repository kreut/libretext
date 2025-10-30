<?php

namespace App;

use App\Exceptions\Handler;
use App\Helpers\Helper;
use \Exception;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Traits\S3;
use App\Traits\GeneralSubmissionPolicy;
use App\Traits\LatePolicy;

use App\Traits\DateFormatter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SubmissionFile extends Model
{
    use S3;
    use DateFormatter;
    use GeneralSubmissionPolicy;
    use LatePolicy;

    protected $guarded = [];

    /**
     * @param $enrolled_users
     * @param Assignment $assignment
     * @param Question $question
     * @return array
     */
    public function getOpenEndedSubmissionsByUser($enrolled_users, Assignment $assignment, Question $question)
    {

        $open_ended_submissions = DB::table('submission_files')
            ->where('assignment_id', $assignment->id)
            ->where('question_id', $question->id)
            ->where('type', '<>', 'a')
            ->get();
        foreach ($open_ended_submissions as $open_ended_submission) {
            $open_ended_submissions_by_user[$open_ended_submission->user_id] = $open_ended_submission;
        }
        $open_ended_submission_info_by_user = [];
        foreach ($enrolled_users as $enrolled_user) {
            if (isset($open_ended_submissions_by_user[$enrolled_user->id])) {
                $open_ended_submission_info_by_user[] = [
                    'user_id' => $enrolled_user->id,
                    'question_id' => $question->id,
                    'name' => $enrolled_user->first_name . ' ' . $enrolled_user->last_name,
                    'last_first' => $enrolled_user->last_name . ', ' . $enrolled_user->first_name,
                    'email' => $enrolled_user->email,
                    'type' => $open_ended_submissions_by_user[$enrolled_user->id]->type,
                    'score' => $open_ended_submissions_by_user[$enrolled_user->id]->score ? Helper::removeZerosAfterDecimal($open_ended_submissions_by_user[$enrolled_user->id]->score) : 'Not Scored.'];
            }

        }
        if ($open_ended_submission_info_by_user) {
            usort($open_ended_submission_info_by_user, function ($a, $b) {
                return $a['name'] <=> $b['name'];
            });
        }
        return $open_ended_submission_info_by_user;
    }

    /**
     * @param $assignment
     * @return string|null
     */
    public function getFullPdfUrl($assignment)
    {

        $full_pdf = $this->where('assignment_id', $assignment->id)
            ->where('user_id', Auth::user()->id)
            ->where('type', 'a')
            ->first();
        if (!$full_pdf) {
            return null;
        }
        return $this->getTemporaryUrl($assignment->id, $full_pdf->submission);
    }

    public
    function getAllInfo(User $user, Assignment $assignment, $solution, $open_ended_submission_type, $submission, $page, $question_id, $original_filename, $date_submitted, $file_feedback, $text_feedback, $date_graded, $file_submission_score, $question_submission_score = null)
    {
        $file_feedback_type = null;
        $file_feedback_url = null;
        if ($file_feedback) {
            $file_feedback_type = (pathinfo($file_feedback, PATHINFO_EXTENSION) === 'mpga') ? 'audio' : 'q';
        }

        return ['user_id' => $user->id,
            'name' => $user->first_name . ' ' . $user->last_name,
            'submission' => $submission,
            'page' => $page,
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
            'open_ended_submission_type' => $open_ended_submission_type];

    }

    /**
     * @param $file
     * @return string
     */
    public
    function submissionStatus($file): string
    {
        if ($file['submission']) {
            return $file['date_graded'] === NUll ? 'ungradedOpenEndedSubmissions' : 'gradedOpenEndedSubmissions';
        } else return false;
    }

    /**
     * @param Assignment $assignment
     * @param string $grade_view
     * @param $users
     * @param int $question_id
     * @return array
     */
    public
    function getUserAndQuestionFileInfo(Assignment $assignment, string $grade_view, $users, int $question_id = 0): array
    {

        $Submission = new Submission();
        ///what if null?
        $extensions = [];
        foreach ($assignment->extensions as $extension) {
            $extensions[$extension->user_id] = $extension->extension;
        }


        foreach ($assignment->submissions as $submission) {
            $question_submission_scores[$submission->question_id][$submission->user_id] = $submission->score;
        }


        foreach ($assignment->submissions as $submission) {
            $question_submission_scores[$submission->question_id][$submission->user_id] = $submission->score;
        }
        $questionFilesByUser = [];
        foreach ($assignment->questionFileSubmissions() as $question_file) {
            $question_file->needs_grading = !$question_file->date_graded || Carbon::parse($question_file->date_submitted) > Carbon::parse($question_file->date_graded);
            $questionFilesByUser[$question_file->question_id][$question_file->user_id] = $question_file;
        }
        $no_uploads_question_ids = [];
        foreach ($questionFilesByUser as $question_file_by_user) {
            foreach ($question_file_by_user as $value) {
                if ($value->type === 'no upload') {
                    $no_uploads_question_ids[] = $value->question_id;
                }

            }
        }
        $no_uploads_question_id = array_unique($no_uploads_question_ids);
        $user_and_submission_file_info = [];

        $assignment_questions_where_student_can_upload_file = $question_id
            ? DB::table('assignment_question')
                ->where('assignment_id', $assignment->id)
                ->where('question_id', $question_id)
                ->get()
            : DB::table('assignment_question')
                ->where('assignment_id', $assignment->id)
                ->where(function ($query) use ($no_uploads_question_id) {
                    $query->whereIn('question_id', $no_uploads_question_id)
                        ->orWhereIn('open_ended_submission_type', ['file', 'text', 'audio']);
                })
                ->orderBy('order')
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


        $assign_to_timings_by_user = $assignment->assignToTimingsByUser();

        $ferpa_mode = (int)request()->cookie('ferpa_mode') === 1 && Auth::user()->id === 5;
        foreach ($assignment_questions_where_student_can_upload_file as $question) {
            foreach ($users as $key => $user) {
                $points[$question->question_id][$user->id] = $question->points;
                //get the assignment info, getting the temporary url of the first submission for viewing
                $submission = $questionFilesByUser[$question->question_id][$user->id]->submission ?? null;
                $applied_late_penalty = $questionFilesByUser[$question->question_id][$user->id]->applied_late_penalty ?? null;
                $date_submitted = $questionFilesByUser[$question->question_id][$user->id]->date_submitted ?? null;
                $page = $questionFilesByUser[$question->question_id][$user->id]->page ?? null;
                $open_ended_submission_type = $question->open_ended_submission_type;
                $question_id = $question->question_id;
                $file_feedback = $questionFilesByUser[$question->question_id][$user->id]->file_feedback ?? null;
                $text_feedback = $questionFilesByUser[$question->question_id][$user->id]->text_feedback ?? null;
                $pasted_content = $questionFilesByUser[$question->question_id][$user->id]->pasted_content ?? 0;
                $text_feedback_editor = $questionFilesByUser[$question->question_id][$user->id]->text_feedback_editor ?? null;
                $original_filename = $questionFilesByUser[$question->question_id][$user->id]->original_filename ?? null;
                $extension = $extensions[$user->user_id] ?? null;
                if ($submission && in_array($assignment->late_policy, ['marked late', 'deduction'])) {
                    $late_file_submission = $this->isLateSubmissionGivenExtensionForMarkedLatePolicy($extension, $assign_to_timings_by_user[$user->id]->due, $date_submitted);
                    if ($late_file_submission) {
                        $late_penalty_percent = $Submission->latePenaltyPercentGivenUserId($user->id, $assignment, Carbon::parse($date_submitted));
                    }
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
                    if ($ferpa_mode) {
                        $grader_name = "Fake Grader";
                    } else {
                        $grader_name = $questionFilesByUser[$question->question_id][$user->id]->grader_name ?? null;
                    }
                }

                $file_submission_score = $questionFilesByUser[$question->question_id][$user->id]->score ?? "N/A";
                $question_submission_score = $question_submission_scores[$question->question_id][$user->id] ?? 0;
                $all_info = $this->getAllInfo($user, $assignment, $solution, $open_ended_submission_type, $submission, $page, $question_id, $original_filename, $date_submitted, $file_feedback, $text_feedback, $date_graded, $file_submission_score, $question_submission_score);
                $all_info['grader_id'] = $grader_id;
                $all_info['text_feedback_editor'] = $text_feedback_editor;
                $all_info['open_ended_submission_type'] = $open_ended_submission_type;
                $all_info['grader_name'] = $grader_name;
                $all_info['late_file_submission'] = $late_file_submission ?? false;
                $all_info['late_penalty_percent'] = $late_penalty_percent ?? null;
                $all_info['applied_late_penalty'] = $applied_late_penalty;
                $all_info['order'] = $question->order;
                $all_info['pasted_content'] = $pasted_content;
                $all_info['submission_status'] = $this->submissionStatus($all_info);
                $user_and_submission_file_info[$question->question_id][$key] = $all_info;

            }
        }
        $reKeyedUserAndSubmissionFileInfo = $this->reKeyUserAndSubmissionFileInfo($user_and_submission_file_info);

        foreach ($reKeyedUserAndSubmissionFileInfo as $question => $user_question) {
            foreach ($user_question as $key => $info) {
                $reKeyedUserAndSubmissionFileInfo[$question][$key]['points'] = $points[$info['question_id']][$info['user_id']];
            }
        }
        $sortedUserAndSubmissionFileInfo = [];
        foreach ($reKeyedUserAndSubmissionFileInfo as $question => $users) {
            usort($users, function ($a, $b) {
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

    /**
     * @param array $assignment_ids
     * @param int $question_id
     * @param bool $score_not_null
     * @return bool
     */
    public
    function hasNonFakeStudentFileSubmissionsForAssignmentQuestion(array $assignment_ids, int $question_id, bool $score_not_null): bool
    {
        $submission_files = DB::table('submission_files')
            ->join('users', 'submission_files.user_id', '=', 'users.id')
            ->whereIn('assignment_id', $assignment_ids)
            ->where('question_id', $question_id);
        if ($score_not_null) {
            $submission_files = $submission_files->whereNotNull('score');

        }
        return $submission_files->where('fake_student', 0)
            ->get()
            ->isNotEmpty();
    }
}
