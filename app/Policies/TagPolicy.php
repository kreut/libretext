<?php

namespace App\Policies;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class TagPolicy
{
    use HandlesAuthorization;


    public function viewAny(User $user)
    {
        return ($user->role !== 3)
            ? Response::allow()
            : Response::deny('You are not allowed to retrieve the tags from the database.');

    }

}
