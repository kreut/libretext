<?php

namespace App\Policies;

use App\Assignment;
use App\Score;
use App\User;
use App\Extension;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class ExtensionPolicy
{
    use HandlesAuthorization;
    use \App\Traits\CommonPolicies;


    /**
     * Determine whether the user can store the extension.
     *
     * @param \App\User $user
     * @param \App\Score $score
     * @return mixed
     */
    public function store(User $user, Extension $extension, int $assignment_id, int $student_user_id)
    {
        return $this->ownsResourceByAssignmentAndStudentOrWasGivenAccessByOwner($user, $assignment_id, $student_user_id)
            ? Response::allow()
            : Response::deny('You are not allowed to create an extension for this student/assignment.');

    }

    /**
     * Determine whether the user can update the extension.
     *
     * @param \App\User $user
     * @param \App\Score $score
     * @return mixed
     */
    public function update(User $user, Extension $extension, int $assignment_id, int $student_user_id)
    {
        return $this->ownsResourceByAssignmentAndStudentOrWasGivenAccessByOwner($user, $assignment_id, $student_user_id)
            ? Response::allow()
            : Response::deny('You are not allowed to update this extension.');

    }

    /**
     * Determine whether the user can view the extension.
     *
     * @param \App\User $user
     * @param \App\Score $score
     * @return mixed
     */
    public function view(User $user, Extension $extension, int $assignment_id, int $student_user_id)
    {
        return $this->ownsResourceByAssignmentAndStudentOrWasGivenAccessByOwner($user, $assignment_id, $student_user_id)
            ? Response::allow()
            : Response::deny('You are not allowed to view this extension.');

    }
}
