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

    /**
     * Determine whether the user can update the score.
     *
     * @param  \App\User  $user
     * @param  \App\Score  $score
     * @return mixed
     */
    public function update(User $user,  Score $score, int $assignment_id, int $student_user_id)
    {

        $assignment = Assignment::find($assignment_id);
        $student_user = User::find($student_user_id);
        //assignment is in user's course and student is enrolled in that course
        $owner_of_course = $assignment ? ($assignment->course->id === $user->id) : false;
        $student_enrolled_in_course = ($assignment && $student_user) ? $student_user->enrollments->contains('id', $assignment->course->id) : false;

        return ($owner_of_course && $student_enrolled_in_course)
            ? Response::allow()
            : Response::deny('You are not allowed to update this score.');

    }

}
