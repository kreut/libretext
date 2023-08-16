<?php

namespace App\Policies;

use App\Analytics;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class AnalyticsPolicy
{
    use HandlesAuthorization;

    public function nursing(User $user, Analytics $analytics, int $nursing_user_id): Response
    {
        return $user->id === $nursing_user_id
            ? Response::allow()
            : Response::deny('You are not allowed to view the nursing analytics.');
    }
}
