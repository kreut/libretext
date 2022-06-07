<?php

namespace App\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class SkillPolicy
{
    use HandlesAuthorization;

    public function index (User $user)
    {
        return ($user->role === 2)
            ? Response::allow()
            : Response::deny('You are not allowed to retrieve the skills from the database.');

    }
}
