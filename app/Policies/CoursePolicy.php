<?php

namespace App\Policies;

use App\Course;
use App\Helpers\Helper;
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
     * @return Response
     */
    public function getAssignmentsForAnonymousUser(User $user, Course $course){
        return ($course->anonymous_users && Helper::isAnonymousUser())
            ? Response::allow()
            : Response::deny('You are not allowed to view these assignments.');
    }

    /**
     * @param User $user
     * @param Course $course
     * @return Response
     */
    public function updateIFrameProperties(User $user, Course $course)
    {
        return ((int)$course->user_id === (int)$user->id)
            ? Response::allow()
            : Response::deny('You are not allowed to update what is shown in the iframe.');
    }
    public function getAssignmentNamesForPublicCourse(User $user, Course $course)
    {
        return $user->role === 2 && $course->public
            ? Response::allow()
            : Response::deny('You are not allowed to access the assignments in that course.');


    }

    public function updateBetaApprovalNotifications(User $user, Course $course)
    {
        return ((int)$course->user_id === (int)$user->id)
            ? Response::allow()
            : Response::deny('You are not allowed to update the Beta course notifications for this course.');

    }

    public function courseAccessForGraders(User $user, Course $course)
    {
        return ((int)$course->user_id === (int)$user->id)
            ? Response::allow()
            : Response::deny('You are not allowed to grant access to all assignments for all graders for this course.');
    }

    public function getAssignmentsAndUsers(User $user, Course $course)
    {

        return ((int)$course->user_id === (int)$user->id)
            ? Response::allow()
            : Response::deny('You are not allowed to download the assignments and users.');
    }

    public function import(User $user, Course $course)
    {

        $owner_of_course = (int)$course->user_id === (int)$user->id;
        $is_public = (int)$course->public === 1;
        $is_instructor = (int)$user->role === 2;
        return ($is_instructor && ($owner_of_course || $is_public))
            ? Response::allow()
            : Response::deny('You are not allowed to import that course.');

    }

    public function getImportable(User $user, Course $course)
    {

        return ((int)$user->role === 2)
            ? Response::allow()
            : Response::deny('You are not allowed to retrieve the importable courses.');

    }

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
        $owner_of_course = ($course->user_id === (int)$user->id);
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
        return $course->enrollments->contains('user_id', $user->id)
            ? Response::allow()
            : Response::deny('You are not allowed to view these scores.');
    }

    public function updateStudentsCanViewWeightedAverage(User $user, Course $course)
    {
        return ($this->ownsCourseByUser($course, $user))
            ? Response::allow()
            : Response::deny('You are not allowed to update being able to view the weighted average.');
    }

    public function updateShowZScores(User $user, Course $course)
    {
        return ($this->ownsCourseByUser($course, $user))
            ? Response::allow()
            : Response::deny('You are not allowed to update being able to view the z-scores.');
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
