<?php

namespace App\Policies;

use App\Score;
use App\User;
use App\Assignment;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class ScorePolicy
{
    use HandlesAuthorization;
    use \App\Traits\CommonPolicies;


    public function overrideScores(User $user, Score $score, Assignment $assignment, array $override_scores)
    {
        $has_access = true;
        $message = '';
        if ($assignment->course->user_id !== $user->id) {
            $has_access = false;
            $message = "You can't override the scores since this is not one of your assignments.";
        } else {
            $enrolled_users = $assignment->course->enrolledUsers->pluck('id')->toArray();
            foreach ($override_scores as $override_score) {
                if (!in_array($override_score['user_id'], $enrolled_users)) {
                    $has_access = false;
                    $message = "You can only override scores if the students are enrolled in your course.";
                }
            }
        }
        return $has_access
            ? Response::allow()
            : Response::deny($message);

    }

    /**
     * Determine whether the user can update the score.
     *
     * @param \App\User $user
     * @param \App\Score $score
     * @return mixed
     */
    public function update(User $user, Score $score, int $assignment_id, int $student_user_id)
    {

        return $this->ownsResourceByAssignmentAndStudentOrWasGivenAccessByOwner($user, $assignment_id, $student_user_id)
            ? Response::allow()
            : Response::deny('You are not allowed to update this score.');

    }

    public function getScoreByAssignmentAndStudent(User $user, Score $score, int $assignment_id, int $student_user_id)
    {

        return $this->ownsResourceByAssignmentAndStudentOrWasGivenAccessByOwner($user, $assignment_id, $student_user_id)
            ? Response::allow()
            : Response::deny('You are not allowed to retrieve this score.');

    }

    public function overTotalPoints(User $user, Score $score, Assignment $assignment)
    {
        return (int)$assignment->course->user_id === (int)$user->id || $assignment->course->isGrader()
            ? Response::allow()
            : Response::deny('You are not allowed to check whether the scores are over the total number of points for this assignment.');
    }

    public function getAssignmentQuestionScoresByUser(User $user, Score $score, Assignment $assignment)
    {
        return (int)$assignment->course->user_id === (int)$user->id || $assignment->course->isGrader()
            ? Response::allow()
            : Response::deny('You are not allowed to retrieve the question scores by user for this assignment.');
    }

    public function getScoreByAssignmentAndQuestion(User $user, Score $score, Assignment $assignment)
    {
        $has_access = false;
        switch ($user->role) {
            case(2):
                $has_access = $assignment->course->user_id === $user->id;
                break;
            case(3):
                $has_access = $assignment->course->enrollments->contains('user_id', $user->id) && $assignment->students_can_view_assignment_statistics;
                break;
        }
        return $has_access
            ? Response::allow()
            : Response::deny("You can't get the scores for an assignment that is not in one of your courses.");

    }


}
