<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\Enrollment;
use App\Helpers\Helper;
use App\Question;
use App\Submission;
use App\SubmissionFile;
use App\SubmissionText;
use App\User;
use Exception;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class AutoGradedAndFileSubmissionController extends Controller
{

    public function getSubmissionTimesByAssignmentAndStudent(Assignment     $assignment,
                                                   Submission     $submission,
                                                   SubmissionFile $submissionFile,
                                                   SubmissionText $submissionText)
    {
        $response['type'] = 'error';
          $authorized = Gate::inspect('getSubmissionTimesByAssignmentAndStudent', [$submission, $assignment]);
          if (!$authorized->allowed()) {
              $response['message'] = $authorized->message();
              return $response;
          }
        try {
            $enrolled_users = $assignment->course->enrolledUsers->sortBy('first_name', SORT_NATURAL | SORT_FLAG_CASE);
            $enrolled_users_by_id = $enrolled_users->pluck('id')->toArray();
            $submissions = $submission->where('assignment_id', $assignment->id)->get();
            foreach ($submissions as $submission) {
                if (!isset($submissions_by_user_question[$submission->user_id])) {
                    $submissions_by_user_question[$submission->user_id] = [];
                }
                $submissions_by_user_question[$submission->user_id][$submission->question_id] = [
                    'first_submitted_at' => $submission->created_at,
                    'last_submitted_at' => $submission->updated_at];
            }
            $submission_files = $submissionFile->where('assignment_id', $assignment->id)->get();
            foreach ($submission_files as $submission_file) {
                if (!isset($submission_files_by_user_question[$submission->user_id])) {
                    $submission_files_by_user_question[$submission_file->user_id] = [];
                }
                $submission_files_by_user_question[$submission_file->user_id][$submission_file->question_id] = [
                    'submitted_at' => $submission->created_at];
            }
            $submission_texts = $submissionText->where('assignment_id', $assignment->id)->get();
            foreach ($submission_texts as $submission_text) {
                if (!isset($submission_texts_by_user_question[$submission_text->user_id])) {
                    $submission_texts_by_user_question[$submission_text->user_id] = [];
                }
                $submission_texts_by_user_question[$submission_text->user_id][$submission_text->question_id] = [
                    'submitted_at' => $submission->created_at];
            }
            $submission_times_by_user = [];
            foreach ($enrolled_users_by_id as $enrolled_user_id) {
                $info['user_id'] = $enrolled_user_id;
                $info['auto_graded'] = $submissions_by_user_question[$enrolled_user_id] ?? null;
                $open_ended = null;
                if (isset($submission_files_by_user_question[$enrolled_user_id])) {
                    $open_ended = $submission_files_by_user_question[$enrolled_user_id];
                }
                if (isset($submission_texts_by_user_question[$enrolled_user_id])) {
                    $open_ended = $submission_texts_by_user_question[$enrolled_user_id];
                }
                $info['open_ended'] = $open_ended;
                $submission_times_by_user[] = $info;
            }
            return $submission_times_by_user;

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the submission times for this assignment.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    /**
     * @param Assignment $assignment
     * @param Submission $submission
     * @param int $download
     * @return array|void
     * @throws Throwable
     */
    public function getAutoGradedSubmissionsByAssignment(Assignment $assignment,
                                                         Submission $submission,
                                                         int        $download)
    {


        $response['type'] = 'error';
        $authorized = Gate::inspect('getAutoGradedSubmissionsByAssignment', [$submission, $assignment]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $enrolled_users = $assignment->course->enrolledUsers->sortBy('first_name', SORT_NATURAL | SORT_FLAG_CASE);
            $questions = DB::table('assignment_question')
                ->join('questions', 'assignment_question.question_id', '=', 'questions.id')
                ->where('assignment_id', $assignment->id)
                ->orderBy('order')
                ->get();
            $questions_by_id = [];
            $enrolled_users_by_id = [];
            $items = [];
            foreach ($questions as $question) {
                $questions_by_id[$question->question_id] = $question;
            }
            foreach ($enrolled_users as $user) {
                $enrolled_users_by_id[$user->id] = [
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'student_id' => $user->student_id];
            }

            $submissions = $submission->where('assignment_id', $assignment->id)->get();
            foreach ($submissions as $submission) {
                if (!isset($submissions_by_user_question[$submission->user_id])) {
                    $submissions_by_user_question[$submission->user_id] = [];
                }
                $question = $questions_by_id[$submission->question_id];
                $submissions_by_user_question[$submission->user_id][$submission->question_id] = $submission->getStudentResponse($submission, $question->technology, true);
            }
            $fields = [['key' => 'name',
                'label' => 'Name',
                'sortable' => true,
                'stickyColumn' => true,
                'isRowHeader' => true],
                ['key' => 'email',
                    'label' => 'Email',
                    'sortable' => true,
                    'stickyColumn' => false],
                ['key' => 'student_ID',
                    'label' => 'Student ID',
                    'stickyColumn' => false]
            ];
            $download_row_data = [['First Name', 'Last Name', 'Student ID', 'Email']];
            foreach ($questions as $key => $question) {
                $order = $key + 1;
                $download_row_data[0][] = "Question $order";
                $fields[] = ['key' => " $order"];
            }
            $download_index = 1;

            foreach ($enrolled_users_by_id as $user_id => $user_info) {
                $current_download_row_data = [
                    $user_info['first_name'],
                    $user_info['last_name'],
                    $user_info['student_id'],
                    $user_info['email']
                ];
                $item = [
                    'first_name' => $user_info['first_name'],
                    'last_name' => $user_info['last_name'],
                    'name' => $user_info['first_name'] . ' ' . $user_info['last_name'],
                    'student_ID' => $user_info['student_id'],
                    'email' => $user_info['email']
                ];

                foreach ($questions as $question) {
                    if (isset($submissions_by_user_question[$user_id][$question->id]) && is_array($submissions_by_user_question[$user_id][$question->id])) {
                        $submissions_by_user_question[$user_id][$question->id] = json_encode($submissions_by_user_question[$user_id][$question->id]);
                    }
                    $current_download_row_data[] = isset($submissions_by_user_question[$user_id][$question->id]) ? trim(preg_replace('/\s\s+/', ' ', $submissions_by_user_question[$user_id][$question->id])) : '-';
                    $item[" $question->order"] = $submissions_by_user_question[$user_id][$question->id] ?? '-';
                }
                $download_row_data[$download_index] = $current_download_row_data;
                $items[] = $item;
                $download_index++;
            }

            if ($download) {
                $assignment_name = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $assignment->name);
                Helper::arrayToCsvDownload($download_row_data, $assignment_name);
                exit;
            }
            $response['type'] = 'success';
            $response['items'] = $items;
            $response['fields'] = $fields;
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the submissions for this assignment.  Please try again or contact us for assistance.";
        }
        return $response;

    }


    /**
     * @param Request $request
     * @param Assignment $assignment
     * @param Question $question
     * @param Submission $Submission
     * @param SubmissionFile $submissionFile
     * @param User $user
     * @return array
     * @throws Throwable
     */
    public function getAutoGradedAndFileSubmissionsByAsssignmentAndQuestionAndStudent(Request        $request,
                                                                                      Assignment     $assignment,
                                                                                      Question       $question,
                                                                                      Submission     $Submission,
                                                                                      SubmissionFile $submissionFile,
                                                                                      User           $user): array
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('getSubmissions', [$Submission, $assignment, $question]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $enrolled_student_ids = $assignment->getEnrolledStudentIdsByAssignment($request->user()->role);
            $enrolled_users = $user->whereIn('id', $enrolled_student_ids)->get();

            $question = $assignment->questions->where('id', $question->id)->first();
            $open_ended_submission_info_by_user = $submissionFile->getOpenEndedSubmissionsByUser($enrolled_users, $assignment, $question);
            $auto_graded_submission_info_by_user = $Submission->getAutoGradedSubmissionsByUser($enrolled_users, $assignment, $question);
            $response['is_native_numerical'] =
            $response['auto_graded_submission_info_by_user'] = array_values($auto_graded_submission_info_by_user);
            $response['open_ended_submission_info_by_user'] = array_values($open_ended_submission_info_by_user);
            $response['assignment_name'] = $assignment->name;
            $response['type'] = 'success';

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the submissions for this assignment.  Please try again or contact us for assistance.";
        }
        return $response;
    }
}
