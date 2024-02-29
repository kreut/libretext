<?php

namespace App\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class AutoReleasePolicy
{
    use HandlesAuthorization;

    public function compareAssignmentToCourseDefault(User $user): Response
    {

        return ($user->role === 2)
            ? Response::allow()
            : Response::deny('You are not allowed to compare the assignment auto-release to the default course auto-release.');


    }
}

