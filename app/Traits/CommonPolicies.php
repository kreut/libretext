<?php


namespace App\Traits;


use App\Assignment;
use App\Grader;
use App\User;

trait CommonPolicies

{
    /**
     * @param $user
     * @param $assignment_id
     * @param $student_user_id
     * @return bool
     */
    public function ownsResourceByAssignmentAndStudentOrWasGivenAccessByOwner($user, $assignment_id, $student_user_id): bool
    {
        $assignment = Assignment::find($assignment_id);
        $student_user = User::find($student_user_id);
        //assignment is in user's course and student is enrolled in that course

        $is_owner_or_grader = $this->isOwnerOrGrader($assignment, $user);
        $student_enrolled_in_course = $assignment && $student_user && $student_user->enrollments->contains('id', $assignment->course->id);
        return ($is_owner_or_grader && $student_enrolled_in_course);
    }

    /**
     * @param $assignment
     * @param $user
     * @return bool
     */
    public function isOwnerOrGrader($assignment, $user): bool
    {
        $is_owner = $assignment && $assignment->course->user_id === $user->id;
        $is_grader = $assignment->course->isGrader();
        if ($is_grader) {
            $course_sections = $assignment->course->sections->pluck('id');
            $grader_sections = Grader::where('user_id', $user->id)->pluck('section_id');
            $is_grader = $course_sections->intersect($grader_sections)->isNotEmpty();
        }
        return $is_owner || $is_grader;
    }

    /**
     * @param $course
     * @param $user
     * @return bool
     */
    public function ownsCourseByUser($course, $user): bool
    {
        //added int because test was failing in instructor course test
        return (int)$user->id === (int)$course->user_id;
    }

    public function isNotStudent($user)
    {
        return $user->role !== 3;
    }
}
