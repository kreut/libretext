<?php

namespace App\Policies;


use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class PasswordPolicy
{
    use HandlesAuthorization;

    public function update(User $user): Response
    {

        return $user->email !== 'anonymous'
            ? Response::allow()
            : Response::deny('You are not allowed to update the password.');

    }
}
