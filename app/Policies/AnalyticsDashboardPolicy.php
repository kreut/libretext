<?php

namespace App\Policies;

use App\AnalyticsDashboard;
use App\Course;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class AnalyticsDashboardPolicy
{
    use HandlesAuthorization;

    /**
     * @param User $user
     * @param AnalyticsDashboard $analyticsDashboard
     * @param Course $course
     * @return Response
     */
    public function show(User $user, AnalyticsDashboard $analyticsDashboard, Course $course): Response
    {

        return $course->user_id === $user->id
            ? Response::allow()
            : Response::deny('You are not allowed to view the analytics dashboard for the course.');
    }
}
