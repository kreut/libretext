<?php

namespace App\Policies;

use App\AssignmentGroup;
use App\Course;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class AssignmentGroupPolicy
{
    use HandlesAuthorization;

    public function store(User $user, AssignmentGroup $assignmentGroup, Course $course){
        return $user->id === (int) $course->user_id
            ? Response::allow()
            : Response::deny('You are not allowed to create an assignment group for this course.');

    }
}
