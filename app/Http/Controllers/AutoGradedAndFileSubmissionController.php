<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\Question;
use App\Submission;
use App\SubmissionFile;
use Exception;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AutoGradedAndFileSubmissionController extends Controller
{
    /**
     * @param Request $request
     * @param Assignment $assignment
     * @param Question $question
     * @param Submission $Submission
     * @return array
     * @throws Exception
     */
    public function getAutoGradedAndFileSubmissionsByAsssignmentAndQuestionAndStudent(Request $request,
                                                     Assignment $assignment,
                                                     Question $question,
                                                     Submission $Submission,
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
            $auto_graded_submission_info_by_user= $Submission->getAutoGradedSubmissionsByUser($enrolled_users, $assignment, $question, 'allStudents');

            $response['auto_graded_submission_info_by_user'] = array_values($auto_graded_submission_info_by_user);
            $response['open_ended_submission_info_by_user'] = array_values( $open_ended_submission_info_by_user);
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
