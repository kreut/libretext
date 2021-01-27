<?php

namespace App\Policies;

use App\Assignment;
use App\Score;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use \App\Traits\CommonPolicies;

class AssignmentPolicy
{
    use HandlesAuthorization;
    use CommonPolicies;

    public function getClickerQuestion(User $user, Assignment $assignment){

        return $assignment->course->enrollments->contains('user_id', $user->id)
            ? Response::allow()
            : Response::deny('You are not allowed to get the clicker questions for this assignment.');

}



    /**
     * Determine whether the user can view the assignment.
     *
     * @param \App\User $user
     * @param \App\Assignment $assignment
     * @return mixed
     */
    public function view(User $user, Assignment $assignment)
    {
        $has_access = $this->canView($user, $assignment);
        return $has_access
            ? Response::allow()
            : Response::deny('You are not allowed to access this assignment.');
    }

    function canView(User $user, Assignment $assignment)
    {
        $has_access = false;
        switch ($user->role) {
            case(2):
                $has_access = $this->ownsCourseByUser($assignment->course, $user);
                break;
            case(3):
                $has_access = $assignment->course->enrollments->contains('user_id', $user->id);
                break;
            case(4):
                $has_access = $assignment->course->isGrader();
                break;
        }
        return $has_access;
    }

    /**
     * Determine whether the user can update the assignment.
     *
     * @param \App\User $user
     * @param \App\Assignment $assignment
     * @return mixed
     */
    public function getQuestionsInfo(User $user, Assignment $assignment)
    {
        return $user->id === (int)$assignment->course->user_id
            ? Response::allow()
            : Response::deny('You are not allowed to get questions for this assignment.');
    }

   /**
     * Determine whether the user can update the assignment.
     *
     * @param \App\User $user
     * @param \App\Assignment $assignment
     * @return mixed
     */
    public function update(User $user, Assignment $assignment)
    {
        return $user->id === (int)$assignment->course->user_id
            ? Response::allow()
            : Response::deny('You are not allowed to update this assignment.');
    }

    /**
     * Determine whether the user can update the assignment.
     *
     * @param \App\User $user
     * @param \App\Assignment $assignment
     * @return mixed
     */
    public function createFromTemplate(User $user, Assignment $assignment)
    {
        return $user->id === (int)$assignment->course->user_id
            ? Response::allow()
            : Response::deny("You are not allowed to create an assignment from this template.");
    }



    /**
     * Determine whether the user can update the assignment.
     *
     * @param \App\User $user
     * @param \App\Assignment $assignment
     * @return mixed
     */
    public function showAssignment(User $user, Assignment $assignment)
    {
        return $user->id === (int)$assignment->course->user_id
            ? Response::allow()
            : Response::deny('You are not allowed to toggle whether students can view an assignment.');
    }

    public function releaseSolutions(User $user, Assignment $assignment)
    {
        $has_access = false;
        switch ($user->role) {
            case(2):
                $has_access = $this->ownsCourseByUser($assignment->course, $user);
                break;
            case(4):
                $has_access = $assignment->course->isGrader();
                break;
        }
        if (!$has_access) {
            $message ='You are not allowed to show/hide solutions.';
        } else {
            $has_access = $assignment->assessment_type !== 'real time';
            if (!$has_access) {
                $message = "Since this assignment is a <strong>real time</strong> assessment type, students will see the solutions immediately.";
            }
        }
        return $has_access
            ? Response::allow()
            : Response::deny($message);
    }

    public function showAssignmentStatistics(User $user, Assignment $assignment)
    {
        $has_access = false;
        switch ($user->role) {
            case(2):
                $has_access = $this->ownsCourseByUser($assignment->course, $user);
                break;
            case(4):
                $has_access = $assignment->course->isGrader();
                break;
        }

        return $has_access
            ? Response::allow()
            : Response::deny('You are not allowed to show/hide assignment statistics.');
    }


    public function scoresAccess(User $user, Assignment $assignment)
    {
        switch ($user->role) {
            case(2):
                $has_access = $assignment->course->user_id === $user->id;
                break;
            case(3):
                $has_access = $assignment->course->enrollments->contains('user_id', $user->id) && $assignment->students_can_view_assignment_statistics;
                break;
            case(4):
                $has_access = $assignment->course->isGrader();
                    break;
            default:
                $has_access = false;
        }
        return $has_access;
    }

    public function getAssignmentSummary(User $user, Assignment $assignment)
    {
        $has_access = $this->canView($user, $assignment);
        return $has_access
            ? Response::allow()
            : Response::deny('You are not allowed to retrieve this summary.');

    }

    public function scoresInfo(User $user, Assignment $assignment)
    {

        return $this->scoresAccess($user, $assignment)
            ? Response::allow()
            : Response::deny('You are not allowed to get these scores.');

    }

    public function showScores(User $user, Assignment $assignment)
    {
        $has_access = false;
        switch ($user->role) {
            case(2):
                $has_access = $this->ownsCourseByUser($assignment->course, $user);
                break;
            case(4):
                $has_access = $assignment->course->isGrader();
                break;
        }

        if (!$has_access) {
            $message = 'You are not allowed to show/hide scores.';
        } else {
            $has_access = $assignment->assessment_type === 'delayed';
            if (!$has_access) {
                $message = "Since this assignment is not a <strong>delayed</strong> assessment type, scores will be shown immediately to the students.";
            }
        }

        return $has_access
            ? Response::allow()
            : Response::deny($message);
    }

    public function showPointsPerQuestion(User $user, Assignment $assignment)
    {
        $has_access = false;
        switch ($user->role) {
            case(2):
                $has_access = $this->ownsCourseByUser($assignment->course, $user);
                break;
            case(4):
                $has_access = $assignment->course->isGrader();
                break;
        }
        if (!$has_access) {
            $message = 'You are not allowed to show/hide the points per question.';
        } else {
            $has_access = $assignment->assessment_type === 'delayed';
            if (!$has_access) {
                $message = "Since this assignment is not a <strong>delayed</strong> assessment type, students should be shown how many points each question is worth.";
            }
        }

        return $has_access
            ? Response::allow()
            : Response::deny($message);
    }


    /**
     * Determine whether the user can delete the assignment.
     *
     * @param \App\User $user
     * @param \App\Assignment $assignment
     * @return mixed
     */
    public function delete(User $user, Assignment $assignment)
    {
        //added (int) because wasn't working in the test
        return $user->id === (int)$assignment->course->user_id
            ? Response::allow()
            : Response::deny('You are not allowed to delete this assignment.');
    }


}
