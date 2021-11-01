<?php

namespace App\Policies;

use App\Helpers\Helper;
use App\InstructorAccessCode;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class InstructorAccessCodePolicy
{
    use HandlesAuthorization;

    public function store(User $user, InstructorAccessCode $instructorAccessCode)
    {

        return Helper::isAdmin()
            ? Response::allow()
            : Response::deny('You are not allowed to get an instructor access code.');
    }

    public function email(User $user, InstructorAccessCode $instructorAccessCode)
    {
        return Helper::isAdmin()
            ? Response::allow()
            : Response::deny('You are not allowed to email an access code.');
    }

}
