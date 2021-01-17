<?php

namespace App\Policies;

use App\Course;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use \App\Traits\CommonPolicies;

class CoursePolicy
{
    use HandlesAuthorization;
    use CommonPolicies;

    /**
     * @param User $user
     * @param Course $course
     * @param int $student_user_id
     * @return Response
     */
    public
    function loginAsStudentInCourse(User $user, Course $course, int $student_user_id)
    {
        $student_enrolled_in_course = $course->enrollments->contains('user_id', $student_user_id);
        $owner_of_course = ($course->user_id === (int) $user->id);
        $is_grader = $course->isGrader();
        //check if the student is in their course.
        return ($student_enrolled_in_course && ($owner_of_course || $is_grader))
            ? Response::allow()
            : Response::deny('You are not allowed to log in as this student.');
    }

    /**
     * Determine whether the user can view any courses.
     *
     * @param \App\User $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return ($user->role !== 3)
            ? Response::allow()
            : Response::deny('You are not allowed to view courses.');
    }

    /**
     * Determine whether the user can view any courses.
     *
     * @param \App\User $user
     * @return mixed
     */
    public function viewCourseScores(User $user, Course $course)
    {
        return ($course->isGrader() || $this->ownsCourseByUser($course, $user))
            ? Response::allow()
            : Response::deny('You are not allowed to view these scores.');
    }

    public function viewCourseScoresByUser(User $user, Course $course)
    {
        return  $course->enrollments->contains('user_id', $user->id) && ($course->students_can_view_weighted_average || $course->finalGrades->letter_grades_released)
            ? Response::allow()
            : Response::deny('You are not allowed to view this score.');
    }

    public function updateStudentsCanViewWeightedAverage(User $user, Course $course)
    {
        return ($this->ownsCourseByUser($course, $user))
            ? Response::allow()
            : Response::deny('You are not allowed to update being able to view the weighted average.');
    }


    /**
     * Determine whether the user can view the course.
     *
     * @param \App\User $user
     * @param \App\Course $course
     * @return mixed
     */
    public function view(User $user, Course $course)
    {
        switch ($user->role) {
            case(2):
                $has_access = $this->ownsCourseByUser($course, $user);
                break;
            case(3):
            {
               $has_access = $course->enrollments->contains('user_id', $user->id);
                break;
            }
            case(4):
            {
                $has_access = $course->isGrader();
                break;
            }
            default:
                $has_access = false;
        }
        return $has_access
            ? Response::allow()
            : Response::deny('You are not allowed to access this course.');
    }

    /**
     * Determine whether the user can view the course.
     *
     * @param \App\User $user
     * @param \App\Course $course
     * @return mixed
     */
    public function createCourseAssignment(User $user, Course $course)
    {
        return $this->ownsCourseByUser($course, $user)
            ? Response::allow()
            : Response::deny('You are not allowed to create assignments for this course.');
    }

    /**
     * Determine whether the user can view the course.
     *
     * @param \App\User $user
     * @param \App\Course $course
     * @return mixed
     */
    public function importAssignment(User $user, Course $course)
    {
        return $this->ownsCourseByUser($course, $user)
            ? Response::allow()
            : Response::deny('You are not allowed to import assignments to this course.');
    }

    public function showCourse(User $user, Course $course)
    {
        return $this->ownsCourseByUser($course, $user)
            ? Response::allow()
            : Response::deny('You are not allowed to show/hide this course.');
    }



    /**
     * Determine whether the user can create courses.
     *
     * @param \App\User $user
     * @return mixed
     */
    public function create(User $user)
    {
        return ($user->role === 2)
            ? Response::allow()
            : Response::deny('You are not allowed to create a course.');
    }

    /**
     * Determine whether the user can update the course.
     *
     * @param \App\User $user
     * @param \App\Course $course
     * @return mixed
     */
    public function update(User $user, Course $course)
    {
        return $this->ownsCourseByUser($course, $user)
            ? Response::allow()
            : Response::deny('You are not allowed to update this course.');
    }

    /**
     * Determine whether the user can delete the course.
     *
     * @param \App\User $user
     * @param \App\Course $course
     * @return mixed
     */
    public function delete(User $user, Course $course)
    {
        return $this->ownsCourseByUser($course, $user)
            ? Response::allow()
            : Response::deny('You are not allowed to delete this course.');
    }



}
