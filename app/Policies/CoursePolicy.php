<?php

namespace App\Policies;

use App\Assignment;
use App\Course;
use App\Helpers\Helper;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use \App\Traits\CommonPolicies;
use Illuminate\Support\Facades\DB;

class CoursePolicy
{
    use HandlesAuthorization;
    use CommonPolicies;

    /**
     * @param User $user
     * @param Course $course
     * @return Response
     */
    public function getNonBetaCoursesAndAssignments(User $user, Course $course): Response
    {
        return in_array($user->role, [2, 5])
            ? Response::allow()
            : Response::deny('You are not allowed to get the courses and assignments.');
    }

    /**
     * @param User $user
     * @param Course $course
     * @return Response
     */
    public function storeTester(User $user, Course $course): Response
    {
        return $user->id === $course->user_id
            ? Response::allow()
            : Response::deny('You are not allowed to add testers to this course.');

    }

    /**
     * @param User $user
     * @param Course $course
     * @return Response
     */
    public function getTesters(User $user, Course $course): Response
    {
        return $user->id === $course->user_id
            ? Response::allow()
            : Response::deny('You are not allowed to get the testers for this course.');

    }

    public function getCommonsCoursesAndAssignments(User $user): Response
    {
        return $user->role === 5
            ? Response::allow()
            : Response::deny('You are not allowed to get the Commons courses and assignments.');

    }

    /**
     * @param User $user
     * @param Course $course
     * @return Response
     */
    public function reset(User $user, Course $course): Response
    {
        return (int)$course->user_id === $user->id || Helper::isAdmin()
            ? Response::allow()
            : Response::deny('You are not allowed to reset that course.');
    }

    public function getConcludedCourses(User $user, Course $course): Response
    {
        return Helper::isAdmin()
            ? Response::allow()
            : Response::deny('You are not allowed to get the the courses to unenroll.');

    }

    /**
     * @param User $user
     * @param Course $course
     * @return Response
     */
    public function getAssignmentsForAnonymousUser(User $user, Course $course): Response
    {
        switch ($user->role) {
            case(2):
                $has_access = ($course->public || Helper::isCommonsCourse($course)) && Helper::hasAnonymousUserSession();
                break;
            case(3):
                $has_access = $course->anonymous_users && Helper::isAnonymousUser();
                break;
            default:
                $has_access = false;
        }
        return $has_access
            ? Response::allow()
            : Response::deny('You are not allowed to view these assignments.');
    }

    /**
     * @param User $user
     * @param Course $course
     * @return Response
     */
    public function updateIFrameProperties(User $user, Course $course): Response
    {
        return ((int)$course->user_id === (int)$user->id)
            ? Response::allow()
            : Response::deny('You are not allowed to update what is shown in the iframe.');
    }

    /**
     * @param User $user
     * @param $courses
     * @param $ordered_courses
     * @return Response
     */
    public function order(User $user, $courses, $ordered_courses): Response
    {
        $owner_courses = DB::table('courses')
            ->where('user_id', $user->id)
            ->select('id')
            ->pluck('id')
            ->toArray();
        $has_access = true;
        foreach ($ordered_courses as $ordered_course) {
            if (!in_array($ordered_course, $owner_courses)) {
                $has_access = false;
            }
        }
        return $has_access
            ? Response::allow()
            : Response::deny('You are not allowed to re-order a course that is not yours.');
    }

    public function getAssignmentNamesForPublicCourse(User $user, Course $course): Response
    {
        return $user->role === 2 && $course->public
            ? Response::allow()
            : Response::deny('You are not allowed to access the assignments in that course.');


    }

    public function updateBetaApprovalNotifications(User $user, Course $course): Response
    {
        return ((int)$course->user_id === (int)$user->id)
            ? Response::allow()
            : Response::deny('You are not allowed to update the Beta course notifications for this course.');

    }

    public function courseAccessForGraders(User $user, Course $course): Response
    {
        return ((int)$course->user_id === (int)$user->id)
            ? Response::allow()
            : Response::deny('You are not allowed to grant access to all assignments for all graders for this course.');
    }

    public function getAssignmentOptions(User $user, Course $course): Response
    {

        return ((int)$course->user_id === (int)$user->id)
            ? Response::allow()
            : Response::deny('You are not allowed to download the assignment options.');
    }

    public function copy(User $user, Course $course): Response
    {
        return (int)$course->user_id === (int)$user->id
            ? Response::allow()
            : Response::deny('You are not allowed to copy a course you do not own.');

    }


    public function import(User $user, Course $course): Response
    {

        $owner_of_course = (int)$course->user_id === (int)$user->id;
        $is_public = (int)$course->public === 1;
        $has_role_that_can_import =  in_array($user->role, [2, 5]);
        $is_non_instructor_question_editor = (int)$user->role === 5;
        return (    $has_role_that_can_import && ($owner_of_course || $is_public)) || ($owner_of_course && $is_non_instructor_question_editor)
            ? Response::allow()
            : Response::deny('You are not allowed to import that course.');

    }

    /**
     * @param User $user
     * @param Course $course
     * @return Response
     */
    public function getImportable(User $user, Course $course): Response
    {

        return in_array($user->role, [2, 5])
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
    function loginAsStudentInCourse(User $user, Course $course, int $student_user_id): Response
    {
        $student_enrolled_in_course = $course->enrollments->contains('user_id', $student_user_id);
        switch ($user->role) {
            case(2):
                $has_access = ($course->user_id === (int)$user->id);
                break;
            case(4):
                $has_access = $course->isGrader();
                break;
            case(6):
                $has_course_access = DB::table('tester_courses')
                    ->where('user_id', $user->id)
                    ->where('course_id', $course->id)
                    ->first();
                $has_student_access = DB::table('tester_students')
                    ->where('tester_user_id', $user->id)
                    ->where('student_user_id', $student_user_id)
                    ->first();
                $has_access = $has_course_access && $has_student_access;
                break;
            default:
                $has_access = false;
        }

        //check if the student is in their course.
        return ($student_enrolled_in_course && $has_access)
            ? Response::allow()
            : Response::deny('You are not allowed to log in as this student.');
    }

    /**
     * Determine whether the user can view any courses.
     *
     * @param User $user
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
     * @param User $user
     * @return mixed
     */
    public function viewCourseScores(User $user, Course $course)
    {

        return ($course->isGrader() || $this->ownsCourseByUser($course, $user))
            ? Response::allow()
            : Response::deny('You are not allowed to view these scores.');
    }

    /**
     * @param User $user
     * @param Course $course
     * @return Response
     */
    public function viewCourseScoresByUser(User $user, Course $course): Response
    {
        return $course->enrollments->contains('user_id', $user->id)
            ? Response::allow()
            : Response::deny('You are not allowed to view these scores.');
    }

    /**
     * @param User $user
     * @param Course $course
     * @return Response
     */
    public function updateStudentsCanViewWeightedAverage(User $user, Course $course): Response
    {
        return ($this->ownsCourseByUser($course, $user))
            ? Response::allow()
            : Response::deny('You are not allowed to update being able to view the weighted average.');
    }

    /**
     * @param User $user
     * @param Course $course
     * @return Response
     */
    public function updateShowZScores(User $user, Course $course): Response
    {
        return ($this->ownsCourseByUser($course, $user))
            ? Response::allow()
            : Response::deny('You are not allowed to update being able to view the z-scores.');
    }

    /**
     * @param User $user
     * @param Course $course
     * @return Response
     */
    public function updateShowProgressReport(User $user, Course $course): Response
    {
        return ($this->ownsCourseByUser($course, $user))
            ? Response::allow()
            : Response::deny('You are not allowed to update being able to view the progress report.');
    }


    /**
     * Determine whether the user can view the course.
     *
     * @param User $user
     * @param Course $course
     * @return mixed
     */
    public function view(User $user, Course $course): Response
    {
        switch ($user->role) {
            case(6):
                $has_access = DB::table('tester_courses')
                    ->where('course_id', $course->id)
                    ->where('user_id', $user->id)
                    ->first();
                break;
            case(5):
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

    public function viewOpen(User $user, Course $course): Response
    {

        return ($user->role === 2 && (Helper::isCommonsCourse($course) || $course->public)) || $course->anonymous_users
            ? Response::allow()
            : Response::deny('You are not allowed to access this course.');
    }

    /**
     * Determine whether the user can view the course.
     *
     * @param User $user
     * @param Course $course
     * @return mixed
     */
    public function createCourseAssignment(User $user, Course $course): Response
    {
        return $this->ownsCourseByUser($course, $user)
            ? Response::allow()
            : Response::deny('You are not allowed to create assignments for this course.');
    }

    /**
     * Determine whether the user can view the course.
     *
     * @param User $user
     * @param Course $course
     * @param Assignment $assignment
     * @return Response
     */
    public function importAssignment(User $user, Course $course, Assignment $assignment): Response
    {

        $has_access = true;
        $message = '';
        if (!$this->ownsCourseByUser($course, $user)) {
            $has_access = false;
            $message = 'You are not allowed to import assignments to this course.';
        }
        if ($has_access) {
            $has_access = $user->role === 2 && ($assignment->course->public
                    || Helper::isCommonsCourse($assignment->course)
                    || $this->ownsCourseByUser($assignment->course, $user));
            if (!$has_access) {
                $message = 'You can only import assignments from your own courses, the Commons, or public courses.';

            }
        }

        return $has_access
            ? Response::allow()
            : Response::deny($message);
    }

    public function showCourse(User $user, Course $course): Response
    {
        return $this->ownsCourseByUser($course, $user)
            ? Response::allow()
            : Response::deny('You are not allowed to show/hide this course.');
    }


    /**
     * Determine whether the user can create courses.
     *
     * @param User $user
     * @return mixed
     */
    public function create(User $user)
    {
        return (in_array($user->role, [2, 5]))
            ? Response::allow()
            : Response::deny('You are not allowed to create a course.');
    }

    /**
     * Determine whether the user can update the course.
     *
     * @param User $user
     * @param Course $course
     * @return mixed
     */
    public function update(User $user, Course $course)
    {
        return $this->ownsCourseByUser($course, $user)
            ? Response::allow()
            : Response::deny('You are not allowed to update this course.');
    }

    /**
     * @param User $user
     * @param Course $course
     * @return Response
     */
    public function delete(User $user, Course $course): Response
    {
        return $this->ownsCourseByUser($course, $user)
            ? Response::allow()
            : Response::deny('You are not allowed to delete this course.');
    }


    /**
     * @param User $user
     * @return Response
     */
    public function getAllCourses(User $user): Response
    {
        return $user->isAdminWithCookie()
            ? Response::allow()
            : Response::deny('You are not allowed to get all courses.');
    }

    /**
     * @param User $user
     * @return Response
     */
    public function getAssignmentNamesIdsByCourse(User $user): Response
    {
        //added (int) because wasn't working in the test
        return in_array($user->role, [2, 5])
            ? Response::allow()
            : Response::deny('You are not allowed to get the names and assignment IDs.');
    }


}
