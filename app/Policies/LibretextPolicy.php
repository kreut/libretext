<?php

namespace App\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class LibretextPolicy
{
    use HandlesAuthorization;

    /**
     * @param User $user
     * @return Response
     */
    public function emailSolutionError(User $user): Response
    {
        return ((int) $user->role === 2)
            ? Response::allow()
            : Response::deny('You are not allowed to send a solution email error.');

    }

}
