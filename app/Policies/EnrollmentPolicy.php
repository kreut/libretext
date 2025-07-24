<?php

namespace App\Policies;


use App\Assignment;
use App\Course;
use App\Helpers\Helper;
use App\Section;
use App\User;
use App\Enrollment;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\DB;

class EnrollmentPolicy
{
    use HandlesAuthorization;

    /**
     * @param User $user
     * @param Enrollment $enrollment
     * @param Course $course
     * @param int $assignment_id
     * @return Response
     */
    public function autoEnrollStudent(User $user, Enrollment $enrollment, Course $course, int $assignment_id): Response
    {
        $valid_assignment = true;

        if ($assignment_id){
            $valid_assignment = in_array($assignment_id, $course->assignments->pluck('id')->toArray());
        }

        $has_access = DB::table('tester_courses')
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();
        return $has_access && $valid_assignment
            ? Response::allow()
            : Response::deny('You are not allowed to auto-enroll a student in this course.');

    }
    public function updateA11yRedirect(User $user, Enrollment $enrollment, Course $course, User $student_user)
    {
        $enrolled_users_ids = $course->enrolledUsers->pluck('id')->toArray();
        $enrolled_in_course = in_array($student_user->id, $enrolled_users_ids);

        return ($enrolled_in_course &&  $course->ownsCourseOrIsCoInstructor($user->id))
            ? Response::allow()
            : Response::deny('You are not allowed to update a11y for this student.');

    }

    public function update(User $user, Enrollment $enrollment, Course $course, User $student_user)
    {
        $enrolled_users_ids = $course->enrolledUsers->pluck('id')->toArray();
        $enrolled_in_course = in_array($student_user->id, $enrolled_users_ids);

        return ($enrolled_in_course &&  $course->ownsCourseOrIsCoInstructor($user->id))
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
        return ($assignment->course->ownsCourseOrIsCoInstructor($user->id) || $assignment->course->isGrader())
            ? Response::allow()
            : Response::deny('You are not allowed to get the enrollments for the course from this assignment.');

    }



    public function details(User $user, Enrollment $enrollment, Course $course)
    {
        return ( $course->ownsCourseOrIsCoInstructor($user->id))
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
