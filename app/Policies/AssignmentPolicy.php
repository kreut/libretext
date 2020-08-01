<?php

namespace App\Policies;

use App\Assignment;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class AssignmentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any assignments.
     *
     * @param \App\User $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        //
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
        //
    }

    /**
     * Determine whether the user can create assignments.
     *
     * @param \App\User $user
     * @return mixed
     */
    public function create(User $user)
    {
        //
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
        //
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
        return $user->id === $assignment->course->user_id
            ? Response::allow()
            : Response::deny('You are not allowed to delete this assignment.');
    }

    /**
     * Determine whether the user can restore the assignment.
     *
     * @param \App\User $user
     * @param \App\Assignment $assignment
     * @return mixed
     */
    public function restore(User $user, Assignment $assignment)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the assignment.
     *
     * @param \App\User $user
     * @param \App\Assignment $assignment
     * @return mixed
     */
    public function forceDelete(User $user, Assignment $assignment)
    {
        //
    }
}
