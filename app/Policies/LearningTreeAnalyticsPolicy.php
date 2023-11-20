<?php

namespace App\Policies;

use App\Helpers\Helper;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class LearningTreeAnalyticsPolicy
{
    use HandlesAuthorization;

    /**
     * @return Response
     */
    public function index(): Response
    {
        return Helper::isAdmin()
            ? Response::allow()
            : Response::deny('You are not allowed to get the Learning Tree analytics.');

    }
}
