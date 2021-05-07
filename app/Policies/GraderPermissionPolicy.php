<?php

namespace App\Policies;

use App\Course;
use App\GraderPermission;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class GraderPermissionPolicy
{
    use HandlesAuthorization;

    public function index(User $user, GraderPermission $graderPermission, Course $course){
        return ((int)$course->user_id === (int)$user->id)
            ? Response::allow()
            : Response::deny('You are not allowed to get the grader permissions for this course.');


    }
}
