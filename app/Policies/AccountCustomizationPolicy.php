<?php

namespace App\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class AccountCustomizationPolicy
{
    use HandlesAuthorization;

    public function update(User $user): Response
    {

        return $user->role === 2
            ? Response::allow()
            : Response::deny('You are not allowed to update account customizations.');
    }
}
