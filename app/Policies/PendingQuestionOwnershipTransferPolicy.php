<?php

namespace App\Policies;

use App\PendingQuestionOwnershipTransfer;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class PendingQuestionOwnershipTransferPolicy
{
    use HandlesAuthorization;

    public function update(User                             $user,
                           PendingQuestionOwnershipTransfer $pendingQuestionOwnershipTransfer,
                           string                           $token): Response
    {

        return $pendingQuestionOwnershipTransfer->where('token', $token)->first()
            ? Response::allow()
            : Response::deny("There are no questions with the associated token.  Please ask the originating instructor to once again update the owner in the Meta-tags page.");

    }
}
