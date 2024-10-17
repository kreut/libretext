<?php

namespace App\Policies;

use App\LinkedAccount;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class LinkedAccountPolicy
{
    use HandlesAuthorization;


    public function unlink(User $user, LinkedAccount  $linkedAccount, User $account_to_unlink){
        $has_access = $linkedAccount->where('user_id', $user->id)
            ->where('linked_to_user_id', $account_to_unlink->id)
            ->exists();
        return $has_access
            ? Response::allow()
            : Response::deny("You are not allowed to unlink that account.");

    }
}
