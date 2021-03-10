<?php

namespace App\Policies;


use App\CannedResponse;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class CannedResponsePolicy
{
    use HandlesAuthorization;

    public function index(User $user)
    {

        return (in_array($user->role, [2,4]))
            ? Response::allow()
            : Response::deny('You are not allowed to get canned responses.');

    }

    public function store(User $user)
    {

        return (in_array($user->role, [2,4]))
            ? Response::allow()
            : Response::deny('You are not allowed to store a canned response.');

    }

    public function destroy(User $user, CannedResponse $cannedResponse)
    {

        return ((int) $cannedResponse->user_id === $user->id)
            ? Response::allow()
            : Response::deny('You are not allowed to delete that canned response.');

    }
}
