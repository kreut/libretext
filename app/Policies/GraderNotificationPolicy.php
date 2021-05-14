<?php

namespace App\Policies;

use App\Course;
use App\GraderNotification;
use App\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Auth\Access\HandlesAuthorization;

class GraderNotificationPolicy
{
    use HandlesAuthorization;

    public function index(User $user, GraderNotification $graderNotification, Course $course)
    {
        return (int) $course->user_id === (int) $user->id
            ? Response::allow()
            : Response::deny("You are not allowed to view the grader notifications for this course.");
    }

    public function update(User $user, GraderNotification $graderNotification, Course $course)
    {
        return (int) $course->user_id === (int) $user->id
            ? Response::allow()
            : Response::deny("You are not allowed to update the grader notifications for this course.");
    }

}
