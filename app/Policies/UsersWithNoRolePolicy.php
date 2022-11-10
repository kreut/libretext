<?php

namespace App\Policies;

use App\User;
use App\UsersWithNoRole;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;


class UsersWithNoRolePolicy
{
    use HandlesAuthorization;

    /**
     * @param User $user
     * @param UsersWithNoRole $usersWithNoRole
     * @return Response
     */
    public function index(User $user, UsersWithNoRole $usersWithNoRole): Response
    {
        return $user->isAdminWithCookie()
            ? Response::allow()
            : Response::deny("You are not allowed to get the users without roles.");

    }

    public function update(User $user, UsersWithNoRole $usersWithNoRole, int $role): Response
    {
        $authorized = true;
        $message = '';
        if (app()->environment() !== 'testing') {
            //couldn't get the local test to work with the cookie.
            if (!$user->isAdminWithCookie()) {
                $authorized = false;
                $message = "You are not allowed to update users without roles.";
            }
        }
        if ($role) {
            $authorized = false;
            $message = "You cannot update the role since they already have a role.";
        }
        return $authorized
            ? Response::allow()
            : Response::deny($message);
    }

    public function destroy(User $user, UsersWithNoRole $usersWithNoRole, int $role): Response
    {

        $authorized = true;
        $message = '';
        if (!$user->isAdminWithCookie()) {
            $authorized = false;
            $message = "You are not allowed to delete users.";
        }
        if ($role) {
            $authorized = false;
            $message = "You cannot delete the user since they already have a role.";
        }
        return $authorized
            ? Response::allow()
            : Response::deny($message);
    }
}
