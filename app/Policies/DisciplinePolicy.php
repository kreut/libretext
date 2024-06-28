<?php

namespace App\Policies;


use App\Helpers\Helper;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class DisciplinePolicy
{
    use HandlesAuthorization;

    /**
     * @param User $user
     * @return Response
     */
    public function index(User $user): Response
    {
        return $user->role !== 3
            ? Response::allow()
            : Response::deny('You are not allowed to retrieve the disciplines.');

    }

    /**
     * @return Response
     */
    public function store(): Response
    {
        return Helper::isAdmin()
            ? Response::allow()
            : Response::deny('You are not allowed to create a new discipline.');

    }

    /**
     * @return Response
     */
    public function update(): Response
    {
        return Helper::isAdmin()
            ? Response::allow()
            : Response::deny('You are not allowed to update a discipline.');

    }

    /**
     * @return Response
     */
    public function destroy(): Response
    {
        return Helper::isAdmin()
            ? Response::allow()
            : Response::deny('You are not allowed to delete a discipline.');

    }
}
