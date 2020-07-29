<?php

namespace App\Policies;

use App\Score;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

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
    public function update(User $user,  Score $score)
    {

        //validate that they are the owner of the course
        $is_owner_of_course = DB::table('courses')
            ->select('id')
            ->where('id', '=', $course_id)
            ->where('user_id', '=', $user->id)
            ->first();
        //validate that the assignment is in the course
        $assignment_is_in_course = DB::table('assignments')
            ->select('id')
            ->where('id', '=', $assignment_id)
            ->where('course_id', '=',  $course_id)
            ->first();
        //validate that the student is enrolled in the course
        $student_is_in_enrolled_in_the_course = DB::table('enrollments')
            ->select('user_id')
            ->where('course_id', '=', $course_id)
            ->where('user_id', '=', $student_user_id)
            ->first();

        return ($is_owner_of_course && $assignment_is_in_course && $student_is_in_enrolled_in_the_course);
    }

}
