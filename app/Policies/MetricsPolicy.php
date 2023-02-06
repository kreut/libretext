<?php

namespace App\Policies;

use App\Helpers\Helper;
use App\Metrics;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class MetricsPolicy
{
    use HandlesAuthorization;

    public function index(User $user, Metrics $metrics): Response
    {
        return Helper::isAdmin()
            ? Response::allow()
            : Response::deny('You are not allowed to get the metrics.');
    }

    public function cellData(User $user, Metrics $metrics): Response
    {
        return Helper::isAdmin()
            ? Response::allow()
            : Response::deny('You are not allowed to get the cell data.');
    }
}
