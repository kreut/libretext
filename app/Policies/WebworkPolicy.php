<?php

namespace App\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class WebworkPolicy
{
    use HandlesAuthorization;

    /**
     * @param User $user
     * @return Response
     */
    public function templates(User $user): Response
    {

        return $user->role !== 3
            ? Response::allow()
            : Response::deny('You are not allowed to get the weBWork templates.');

    }
}
