<?php

namespace App\Policies;

use App\AssignmentGroupWeight;
use App\Course;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class AssignmentGroupWeightPolicy
{
    use HandlesAuthorization;

    public function getAssignmentGroupWeights(User $user, AssignmentGroupWeight $assignmentGroupWeight, Course $course){
        return $user->id === (int) $course->user_id
            ? Response::allow()
            : Response::deny('You are not allowed to get the assignment group weights.');

    }

    public function update(User $user, AssignmentGroupWeight $assignmentGroupWeight, Course $course){
        return $user->id === (int) $course->user_id
            ? Response::allow()
            : Response::deny('You are not allowed to update the assignment group weights.');

    }
}
