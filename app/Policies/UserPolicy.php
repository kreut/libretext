<?php

namespace App\Policies;

use App\Course;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    use HandlesAuthorization;

    private $admins;

    public function __construct()
    {

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



}
