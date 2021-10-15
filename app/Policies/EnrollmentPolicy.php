<?php

namespace App\Policies;


use App\Assignment;
use App\Course;
use App\Section;
use App\User;
use App\Enrollment;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class EnrollmentPolicy
{
    use HandlesAuthorization;

    public function update(User $user, Enrollment $enrollment, Course $course, User $student_user)
    {
        $enrolled_users_ids = $course->enrolledUsers->pluck('id')->toArray();
        $enrolled_in_course = in_array($student_user->id, $enrolled_users_ids);

        return ($enrolled_in_course && (int) $course->user_id === $user->id)
            ? Response::allow()
            : Response::deny('You are not allowed to move this student.');

    }


    public function destroy(User $user, Enrollment $enrollment, Section $section, User $student_user)
    {
        $enrolled_users_ids = $section->enrolledUsers->pluck('id')->toArray();
        $enrolled_in_course = in_array($student_user->id, $enrolled_users_ids);

        return ($enrolled_in_course && (int) $section->course->user_id === $user->id)
            ? Response::allow()
            : Response::deny('You are not allowed to unenroll this student.');

    }



    public function enrollmentsFromAssignment(User $user, Enrollment $enrollment, Assignment $assignment)
    {
        return ((int) $assignment->course->user_id === $user->id || $assignment->course->isGrader())
            ? Response::allow()
            : Response::deny('You are not allowed to get the enrollments for the course from this assignment.');

    }



    public function details(User $user, Enrollment $enrollment, Course $course)
    {
        return ((int) $course->user_id === $user->id)
            ? Response::allow()
            : Response::deny('You are not allowed to view these enrollment details.');

    }

    public function view(User $user)
    {
        return ($user->role === 3)
            ? Response::allow()
            : Response::deny('You must be a student to view your enrollments.');

    }

    public function store(User $user)
    {
        return ($user->role === 3)
            ? Response::allow()
            : Response::deny('You must be a student to enroll in a course.');

    }
}
