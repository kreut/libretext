<?php


namespace App\Traits;


use App\Assignment;
use App\User;

trait CommonPolicies

{
    public function ownsResourceByAssignmentAndStudent($user, $assignment_id, $student_user_id)
    {
        $assignment = Assignment::find($assignment_id);
        $student_user = User::find($student_user_id);
        //assignment is in user's course and student is enrolled in that course
        $owner_of_course = $assignment ? ($assignment->course->id === $user->id) : false;
        $student_enrolled_in_course = ($assignment && $student_user) ? $student_user->enrollments->contains('id', $assignment->course->id) : false;
        return ($owner_of_course && $student_enrolled_in_course);
    }

    public function ownsCourseByUser($course, $user){

        return $user->id === $course->user_id;
    }
}
