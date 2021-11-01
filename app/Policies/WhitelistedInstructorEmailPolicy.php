<?php

namespace App\Policies;

use App\Helpers\Helper;
use App\User;
use App\WhitelistedInstructorEmail;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class WhitelistedInstructorEmailPolicy
{
    use HandlesAuthorization;

    public function index(User $user, WhitelistedInstructorEmail $whiteListedInstructorEmail)
    {
        return Helper::isAdmin()
            ? Response::allow()
            : Response::deny('You are not allowed to view the white listed instructor emails.');

    }

    public function store(User $user, WhitelistedInstructorEmail $whiteListedInstructorEmail)
    {
        return Helper::isAdmin()
            ? Response::allow()
            : Response::deny('You are not allowed to update the whitelisted instructor email list.');

    }
}
