<?php

namespace App\Policies;

use App\LoginAsUser;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class LoginAsUserPolicy
{
    use HandlesAuthorization;

    public
    function exitLoginAs(User $user, LoginAsUser $loginAsUser): Response
    {
        $has_access = $loginAsUser->where('logged_in_as_user_id', $user->id)->first() !== null;
        return $has_access
            ? Response::allow()
            : Response::deny("You are not allowed to exit logging in as a user.");
    }
}
