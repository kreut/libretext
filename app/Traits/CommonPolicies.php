<?php


namespace App\Traits;


use App\Assignment;
use App\Grader;
use App\User;

trait CommonPolicies

{
    public function ownsResourceByAssignmentAndStudentOrWasGivenAccessByOwner($user, $assignment_id, $student_user_id)
    {
        $assignment = Assignment::find($assignment_id);
        $student_user = User::find($student_user_id);
        //assignment is in user's course and student is enrolled in that course

        $owner_of_course = $assignment ? ($assignment->course->user_id === $user->id) : false;
        $is_grader = $assignment->course->isGrader();
        if ($is_grader) {
            $course_sections = $assignment->course->sections->pluck('id');
            $grader_sections = Grader::where('user_id', $user->id)->pluck('section_id');
            $is_grader = $course_sections->intersect($grader_sections)->isNotEmpty();
        }

        $student_enrolled_in_course = ($assignment && $student_user) ? $student_user->enrollments->contains('id', $assignment->course->id) : false;
      return (($owner_of_course || $is_grader) && $student_enrolled_in_course);
    }

    public function ownsCourseByUser($course, $user)
    {
        //added int because test was failing in instructor course test
        return (int)$user->id === (int)$course->user_id;
    }

    public function isNotStudent($user)
    {
        return $user->role !== 3;
    }
}
