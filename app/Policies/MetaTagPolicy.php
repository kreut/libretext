<?php

namespace App\Policies;
use App\Helpers\Helper;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class MetaTagPolicy
{
    use HandlesAuthorization;

    /**
     * @param User $user
     * @return Response
     */
    public function getMetaTagsByCourseAssignment(User $user): Response
    {
        return ($user->isAdminWithCookie())
            ? Response::allow()
            : Response::deny('You are not allowed to retrieve the meta-tags from the database.');

    }

    public function update(User $user): Response
    {
        return ($user->isAdminWithCookie())
            ? Response::allow()
            : Response::deny('You are not allowed to update the meta-tags.');

    }


}
