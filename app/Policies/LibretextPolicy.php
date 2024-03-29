<?php

namespace App\Policies;

use App\Assignment;
use App\Libretext;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\DB;

class LibretextPolicy
{
    use HandlesAuthorization;

    public function migrate(User $user): Response
    {

        return $user->isMe()
            ? Response::allow()
            : Response::deny('You are not allowed to migrate questions from the libraries to ADAPT.');

    }

    /**
     * @param User $user
     * @return Response
     */
    public function emailSolutionError(User $user): Response
    {
        return ((int)$user->role === 2)
            ? Response::allow()
            : Response::deny('You are not allowed to send a solution email error.');

    }

}
