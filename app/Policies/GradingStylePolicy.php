<?php

namespace App\Policies;

use App\GradingStyle;
use App\Traits\CommonPolicies;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class GradingStylePolicy
{
    use HandlesAuthorization;
    use CommonPolicies;

    /**
     * @param User $user
     * @param GradingStyle $gradingStyle
     * @return Response
     */
    public function index(User $user, GradingStyle $gradingStyle): Response
    {
        return in_array($user->role, [2, 4])
            ? Response::allow()
            : Response::deny('You are not allowed to retrieve the grading styles.');
    }

}
