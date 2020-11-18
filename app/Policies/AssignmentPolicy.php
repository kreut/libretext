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


    /**
     * Determine whether the user can view the assignment.
     *
     * @param \App\User $user
     * @param \App\Assignment $assignment
     * @return mixed
     */
    public function view(User $user, Assignment $assignment)
    {

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
            default:
                false;
        }
        return $has_access
            ? Response::allow()
            : Response::deny('You are not allowed to access this assignment.');
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

        return $has_access
            ? Response::allow()
            : Response::deny('You are not allowed to show/hide solutions.');
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


    public function scoresAccess(User $user, Assignment $assignment) {
        switch ($user->role) {
            case(2):
                $has_access = $assignment->course->user_id === $user->id;
                break;
            case(3):
                $has_access = $assignment->course->enrollments->contains('user_id', $user->id) && $assignment->students_can_view_assignment_statistics;
                break;
            default:
                $has_access = false;
        }
        return $has_access;
    }
    public function totalPointsInfo(User $user, Assignment $assignment)
    {
        return $this->scoresAccess($user, $assignment)
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

        return $has_access
            ? Response::allow()
            : Response::deny('You are not allowed to show/hide scores.');
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
