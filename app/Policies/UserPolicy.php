<?php

namespace App\Policies;

use App\Course;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\DB;

class UserPolicy
{
    use HandlesAuthorization;

    private $admins;

    public function __construct()
    {

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

        return $user->isAdminWithCookie()
            ? Response::allow()
            : Response::deny('You are not allowed to retrieve the users from the database.');
    }

    /**
     * @param User $user
     * @return Response
     */
    public
    function loginAs(User $user): Response
    {

        return $user->isAdminWithCookie()
            ? Response::allow()
            : Response::deny('You are not allowed to log in as a different user.');
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
