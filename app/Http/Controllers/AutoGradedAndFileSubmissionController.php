<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\Question;
use App\Submission;
use App\SubmissionFile;
use Exception;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class AutoGradedAndFileSubmissionController extends Controller
{

    /**
     * @param Assignment $assignment
     * @param Submission $submission
     * @return array
     * @throws \Throwable
     */
    public function getAutoGradedSubmissionsByAssignment(Assignment $assignment,
                                                         Submission $submission): array
    {


        $response['type'] = 'error';
        $authorized = Gate::inspect('getAutoGradedSubmissionsByAssignment', [$submission, $assignment]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $enrolled_users = $assignment->course->enrolledUsers->sortBy('first_name');
            $questions = DB::table('assignment_question')
                ->join('questions', 'assignment_question.question_id', '=', 'questions.id')
                ->where('assignment_id', $assignment->id)
                ->orderBy('order')
                ->get();
            $questions_by_id = [];
            $enrolled_users_by_id = [];
            $download_rows = [];
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
                $submissions_by_user_question[$submission->user_id][$submission->question_id] = $submission->getStudentResponse($submission, $question->technology);
            }

            $download_fields = new \stdClass();
            $download_fields->{'First Name'} = 'first_name';
            $download_fields->{'Last Name'} = 'last_name';
            $download_fields->Email = 'email';
            $download_fields->{'Student ID'} = 'student_id';
            foreach ($questions as $key => $question) {
                $question_num = $key + 1;
                $download_fields->{" $question_num"} = $key + 1;
            }

            foreach ($enrolled_users_by_id as $user_id => $user_info) {
                $download_row_data = $item = [
                    'first_name' => $user_info['first_name'],
                    'last_name' => $user_info['last_name'],
                    'student_ID' => $user_info['student_id'],
                    'email' => $user_info['email']
                ];
                foreach ($questions as $question) {

                    $download_row_data["$question->order"] = $submissions_by_user_question[$user_id][$question->id] ?? '-';
                    $item[" $question->order"] = $submissions_by_user_question[$user_id][$question->id] ?? '-';
                }
                $download_rows[] = $download_row_data;
                $items[] = $item;
            }

            $response['type'] = 'success';
            $response['download_rows'] = $download_rows;
            $response['items'] = $items;
            $response['download_fields'] = $download_fields;
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
     * @return array
     * @throws \Throwable
     */
    public function getAutoGradedAndFileSubmissionsByAsssignmentAndQuestionAndStudent(Request        $request,
                                                                                      Assignment     $assignment,
                                                                                      Question       $question,
                                                                                      Submission     $Submission,
                                                                                      SubmissionFile $submissionFile): array
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('getSubmissions', [$Submission, $assignment, $question]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $enrolled_users = $assignment->course->enrolledUsers;
            $question = $assignment->questions->where('id', $question->id)->first();

            $open_ended_submission_info_by_user = $submissionFile->getOpenEndedSubmissionsByUser($enrolled_users, $assignment, $question);
            $auto_graded_submission_info_by_user = $Submission->getAutoGradedSubmissionsByUser($enrolled_users, $assignment, $question, 'allStudents');

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
