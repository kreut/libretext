<?php

namespace App\Policies;

use App\Helpers\Helper;
use App\LtiRegistration;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class LtiRegistrationPolicy
{
    use HandlesAuthorization;

    public function index(User $user, LtiRegistration $ltiRegistration)
    {
        return Helper::isAdmin()
            ? Response::allow()
            : Response::deny('You are not allowed to view the LTI registrations.');

    }
    public function active(User $user, LtiRegistration $ltiRegistration)
    {
        return (int) $user->id === 1
            ? Response::allow()
            : Response::deny('You are not allowed to toggle the LTI registration.');

    }
    public function store(User $user, LtiRegistration $ltiRegistration)
    {
        return (int) $user->id === 1
            ? Response::allow()
            : Response::deny('You are not allowed to save the LTI registration.');

    }
}
