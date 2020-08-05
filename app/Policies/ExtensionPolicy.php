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

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    private function _canModifyExtensions($user, $assignment_id, $student_user_id)
    {
        $assignment = Assignment::find($assignment_id);
        $student_user = User::find($student_user_id);
        //assignment is in user's course and student is enrolled in that course
        $owner_of_course = $assignment ? ($assignment->course->id === $user->id) : false;
        $student_enrolled_in_course = ($assignment && $student_user) ? $student_user->enrollments->contains('id', $assignment->course->id) : false;
        return ($owner_of_course && $student_enrolled_in_course);
    }

    /**
     * Determine whether the user can store the extension.
     *
     * @param \App\User $user
     * @param \App\Score $score
     * @return mixed
     */
    public function store(User $user, Extension $extension, int $assignment_id, int $student_user_id)
    {
        return $this->_canModifyExtensions($user, $assignment_id, $student_user_id)
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
        return $this->_canModifyExtensions($user, $assignment_id, $student_user_id)
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
        return $this->_canModifyExtensions($user, $assignment_id, $student_user_id)
            ? Response::allow()
            : Response::deny('You are not allowed to view this extension.');

    }
}
