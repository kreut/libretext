<?php

namespace App\Policies;

use App\Course;
use App\Helpers\Helper;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserPolicy
{
    use HandlesAuthorization;

    private $admins;
    /**
     * @param User $user
     * @return Response
     */
    public function getStudentsToInvite(User $user): Response
    {
        return $user->role === 2
            ? Response::allow()
            : Response::deny("You are not allowed to get students to invite.");
    }

    /**
     * @param User $user
     * @param User $instructor_user
     * @param int $course_id
     * @param int $section_id
     * @return Response
     */
    public function inviteStudent(User $user, User $instructor_user, int $course_id, int $section_id): Response
    {

        $course_owner = DB::table('sections')
            ->join('courses', 'sections.course_id', '=', 'courses.id')
            ->where('courses.user_id', $user->id)
            ->where(function ($query) use ($course_id, $section_id) {
                $query->where('sections.course_id', '=', $course_id)
                    ->where('sections.id', '=', $section_id);
            })
            ->exists();
        return $course_owner
            ? Response::allow()
            : Response::deny("You are not allowed to send student invitations to this course.");
    }


    /**
     * @param User $user
     * @return Response
     */
    public function getStudentRosterUploadTemplate(User $user): Response
    {
        return $user->role === 2
            ? Response::allow()
            : Response::deny("You are not allowed to get the student roster upload template.");
    }


    /**
     * @param User $user
     * @param User $instructor_user
     * @param Course $course
     * @return Response
     */
    public function revokeStudentInvitations(User $user, User $instructor_user, Course $course): Response
    {
        return $course->user_id === request()->user()->id
            ? Response::allow()
            : Response::deny("You are not allowed to revoke student invitations for this course.");
    }


    /**
     * @param User $user
     * @return Response
     */
    public function getSignedUserId(User $user): Response
    {
        return in_array($user->role, [2, 3])
            ? Response::allow()
            : Response::deny("You are not allowed to retrieve a signed user id.");
    }

    /**
     * @param User $user
     * @return Response
     */
    public function toggleStudentView(User $user): Response
    {
        return $user->role === 2 || $user->fake_student
            ? Response::allow()
            : Response::deny("You are not allowed to toggle the student view.");

    }

    /**
     * @param User $user
     * @param User $student
     * @return Response
     */
    public function destroy(User $user, User $student): Response
    {
        $tester_student_of_user = DB::table('tester_students')
            ->where('tester_user_id', $user->id)
            ->where('student_user_id', $student->id)
            ->first();
        return $tester_student_of_user
            ? Response::allow()
            : Response::deny("You are not allowed to remove this student.");
    }

    public function updateStudentEmail(User $user, User $instructor, int $student_id)
    {

        $enrolled_in_one_of_instructors_courses = DB::table('enrollments')
            ->join('courses', 'enrollments.course_id', '=', 'courses.id')
            ->where('enrollments.user_id', $student_id)
            ->where('courses.user_id', $instructor->id)
            ->first();
        return $enrolled_in_one_of_instructors_courses
            ? Response::allow()
            : Response::deny("You are not allowed to update this student's email.");

    }

    /**
     * @param User $user
     * @return Response
     */
    public function setAnonymousUserSession(User $user): Response
    {
        return $user->role === 2
            ? Response::allow()
            : Response::deny('You are not allowed to set an anonymous user session.');

    }

    /**
     * @param User $user
     * @return Response
     */
    public function getAll(User $user): Response
    {

        return Helper::isAdmin()
            ? Response::allow()
            : Response::deny('You are not allowed to retrieve the users from the database.');
    }



    /**
     * @param User $user
     * @return Response
     */
    public
    function updateEmail(User $user): Response
    {

        return Helper::isAdmin()
            ? Response::allow()
            : Response::deny("You are not allowed to update emails.");
    }
    /**
     * @param User $user
     * @return Response
     */
    public
    function updateRole(User $user): Response
    {

        return Helper::isAdmin()
            ? Response::allow()
            : Response::deny("You are not allowed to update the user roles.");
    }

    /**
     * @param User $user
     * @return Response
     */
    public
    function getUserInfoByEmail(User $user): Response
    {

        return Helper::isAdmin()
            ? Response::allow()
            : Response::deny("You are not allowed to get the user info by email.");
    }

    /**
     * @param User $user
     * @param User $login_as_user
     * @param string $email
     * @return Response
     */
    public
    function loginAs(User $user, User $login_as_user, string $email): Response
    {
        $message = 'You are not allowed to log in as a different user.';
        if ($user->id == 7665) {
            $has_access = strpos($email, 'estrellamountain.edu') !== false;
            if (!$has_access) {
                $message = "You are not allowed to log in as $email.";
            }
        } else {
            $has_access = Helper::isAdmin();
        }
        return $has_access
            ? Response::allow()
            : Response::deny($message);
    }

    /**
     * @param User $user
     * @return Response
     */
    public function getAllQuestionEditors(User $user): Response
    {
        return in_array($user->role, [2, 5])
            ? Response::allow()
            : Response::deny('You are not allowed to get all question editors.');


    }


}
