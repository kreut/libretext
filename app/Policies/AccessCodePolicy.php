<?php

namespace App\Policies;

use App\Helpers\Helper;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class AccessCodePolicy
{
    use HandlesAuthorization;

    public function store(User $user)
    {
        return Helper::isAdmin()
            ? Response::allow()
            : Response::deny('You are not allowed to get an access code.');
    }

    public function email(User $user)
    {
        return Helper::isAdmin()
            ? Response::allow()
            : Response::deny('You are not allowed to email an access code.');
    }

}
