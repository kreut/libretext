<?php

namespace App\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\DB;

class WebworkPolicy
{
    use HandlesAuthorization;
    public function uploadAttachment(User $user): Response
    {

        return in_array($user->role, [2,5])
            ? Response::allow()
            : Response::deny("You are not allowed to upload webwork attachments.");
    }
}
