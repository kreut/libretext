<?php

namespace App\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class CKEditorPolicy
{
    use HandlesAuthorization;

    public function upload(User $user){
        return (int) $user->role === 2
            ? Response::allow()
            : Response::deny('You are not allowed to upload images.');

    }
}
