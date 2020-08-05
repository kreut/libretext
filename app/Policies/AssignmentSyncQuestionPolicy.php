<?php

namespace App\Policies;

use App\Assignment;
use App\AssignmentSyncQuestion;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class AssignmentSyncQuestionPolicy
{
    use HandlesAuthorization;


    /**
     * Determine whether the user can delete the question in the assignment.
     *
     * @param \App\User $user
     * @param \App\Assignment $assignment
     * @return mixed
     */
    public function delete(User $user, AssignmentSyncQuestion $assignmentSyncQuestion, Assignment $assignment)
    {
        return $user->id === ($assignment->course->user_id)
            ? Response::allow()
            : Response::deny('You are not allowed to remove this question from this assignment.');
    }

    /**
     * Determine whether the user can delete the question in the assignment.
     *
     * @param \App\User $user
     * @param \App\Assignment $assignment
     * @return mixed
     */
    public function add(User $user, AssignmentSyncQuestion $assignmentSyncQuestion, Assignment $assignment)
    {
        return $user->id === ($assignment->course->user_id)
            ? Response::allow()
            : Response::deny('You are not allowed to add a question to this assignment.');
    }
}
