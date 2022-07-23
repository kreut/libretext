<?php

namespace App\Policies;
use App\Helpers\Helper;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class TagPolicy
{
    use HandlesAuthorization;

    /**
     * @param User $user
     * @return Response
     */
    public function viewAny(User $user): Response
    {
        return ($user->role !== 3)
            ? Response::allow()
            : Response::deny('You are not allowed to retrieve the tags from the database.');

    }


}
