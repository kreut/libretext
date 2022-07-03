<?php

namespace App\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class LearningOutcomePolicy
{
    use HandlesAuthorization;

    /**
     * @param User $user
     * @return Response
     */
    public function getLearningOutcomes(User $user): Response
    {
        return (in_array($user->role,[2, 5]))
            ? Response::allow()
            : Response::deny('You are not allowed to retrieve the learning outcomes from the database.');

    }

    /**
     * @param User $user
     * @return Response
     */
    public function getDefaultSubject(User $user): Response
    {
        return (in_array($user->role,[2, 5]))
            ? Response::allow()
            : Response::deny('You are not allowed to retrieve a default subject from the database.');
    }

}
