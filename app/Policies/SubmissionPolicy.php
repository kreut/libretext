<?php

namespace App\Policies;

use App\Assignment;
use App\User;
use App\Submission;
use App\Question;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\DB;
use Illuminate\Auth\Access\Response;
use App\Traits\GeneralSubmissionPolicy;

class SubmissionPolicy
{
    use HandlesAuthorization;
    use GeneralSubmissionPolicy;


    /**
     * @param User $user
     * @param Submission $submission
     * @param Assignment $assignment
     * @param Question $question
     * @return Response
     */
    public function getSubmissions(User $user, Submission $submission, Assignment $assignment, Question $question): Response
    {

        $has_access = true;
        $message = '';

        if (!in_array($question->id, $assignment->questions->pluck('id')->toArray())) {
            $message = "You can't get the submissions for a question that is not in one of your assignments.";
            $has_access = false;
        }

        if ($assignment->course->user_id !== $user->id) {
            $message = "You can't get the submissions for an assignment that is not in one of your courses.";
            $has_access = false;
        }

        return $has_access
            ? Response::allow()
            : Response::deny($message);

    }

    /**
     * @param User $user
     * @param Submission $submission
     * @param Assignment $assignment
     * @return Response
     */
    public function getAutoGradedSubmissionsByAssignment(User $user, Submission $submission, Assignment $assignment): Response
    {


        return $assignment->course->user_id === $user->id
            ? Response::allow()
            : Response::deny("You can't get the auto-graded submissions for an assignment that is not in one of your courses.");

    }

    public function reset(User $user, Submission $submission, Assignment $assignment, int $question_id)
    {
        $instructor_user_id = session()->get('instructor_user_id');
        $is_fake_student = $user->fake_student && session()->get('instructor_user_id');
        $assignment_questions = $assignment->questions->pluck('id')->toArray();


        return  $instructor_user_id &&     $is_fake_student && in_array($question_id, $assignment_questions)
            ? Response::allow()
            : Response::deny("You are not allowed to reset this submissions.");


    }

    public function updateScores(User       $user,
                                 Submission $submission,
                                 Assignment $assignment,
                                 Question   $question,
                                 array      $user_ids)
    {

        $has_access = true;
        $message = '';
        $enrolled_users = $assignment->course->enrolledUsers->pluck('id')->toArray();
        foreach ($user_ids as $user_id) {
            if (!in_array($user_id, $enrolled_users)) {
                $has_access = false;
                $message = "You can't update scores for students not enrolled in your course.";
            }
        }

        if ($has_access && !in_array($question->id, $assignment->questions->pluck('id')->toArray())) {
            $message = "You can't update the scores for a question that is not in one of your assignments.";
            $has_access = false;
        }

        if ($has_access && $assignment->course->user_id !== $user->id) {
            $message = "You can't update the scores for an assignment not in one of your courses.";
            $has_access = false;
        }

        return $has_access
            ? Response::allow()
            : Response::deny($message);
    }

    /**
     * @param User $user
     * @param Submission $submission
     * @param $assignment
     * @param int $assignment_id
     * @param int $question_id
     * @return Response]
     */
    public function store(User $user, $submission, $assignment, int $assignment_id, int $question_id)
    {
        $response = $this->canSubmitBasedOnGeneralSubmissionPolicy($user, $assignment, $assignment_id, $question_id);
        $has_access = $response['type'] === 'success';
        return $has_access
            ? Response::allow()
            : Response::deny($response['message']);
    }

    public function delete(User $user, $submission, $assignment, int $assignment_id, int $question_id)
    {
        $response = $this->canSubmitBasedOnGeneralSubmissionPolicy($user, $assignment, $assignment_id, $question_id);
        $has_access = $response['type'] === 'success';
        return $has_access
            ? Response::allow()
            : Response::deny("You are not allowed to reset this text submission. {$response['message']}");
    }

    public function submissionPieChartData(User $user, Submission $submission, Assignment $assignment, Question $question)
    {

        $question_in_assignment = DB::table('assignment_question')
            ->where('assignment_id', $assignment->id)
            ->where('question_id', $question->id)
            ->first();

        switch ($user->role) {
            case(2):
                $has_access = $question_in_assignment && ($assignment->course->user_id === (int)$user->id);
                break;
            case(3):
                $has_access = $question_in_assignment && $assignment->course->enrollments->contains('user_id', $user->id);
                break;
            case(4):
                $has_access = $question_in_assignment && $assignment->course->isGrader();
                break;
            default:
                $has_access = false;
        }
        return $has_access
            ? Response::allow()
            : Response::deny('You are not allowed to view this summary.');
    }


}
