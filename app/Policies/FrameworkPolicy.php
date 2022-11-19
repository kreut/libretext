<?php

namespace App\Policies;

use App\Framework;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class FrameworkPolicy
{
    use HandlesAuthorization;

    public function export(User $user): Response
    {
        return in_array($user->role,[2,5])
            ? Response::allow()
            : Response::deny('You are not allowed to export this framework.');

    }

    /**
     * @param User $user
     * @return Response
     */
    public function store(User $user): Response
    {
        return in_array($user->role,[2,5])
            ? Response::allow()
            : Response::deny('You are not allowed to create a framework.');

    }

    /**
     * @param User $user
     * @return Response
     */
    public function index(User $user): Response
    {
        return in_array($user->role,[2,5])
            ? Response::allow()
            : Response::deny('You are not allowed to get the frameworks.');

    }

    /**
     * @param User $user
     * @return Response
     */
    public function show(User $user): Response
    {
        return in_array($user->role,[2,5])
            ? Response::allow()
            : Response::deny('You are not allowed to view the framework.');

    }

    /**
     * @param User $user
     * @param Framework $framework
     * @return Response
     */
    public function update(User $user, Framework $framework): Response
    {
        return $user->id === $framework->user_id
            ? Response::allow()
            : Response::deny('You are not allowed to edit this framework.');

    }

    public function destroy(User $user, Framework $framework): Response
    {
        return $user->id === $framework->user_id
            ? Response::allow()
            : Response::deny('You are not allowed to delete this framework.');

    }
}
