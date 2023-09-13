<?php

namespace App\Policies;

use App\Assignment;
use App\PassbackByAssignment;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class PassbackByAssignmentPolicy
{
    use HandlesAuthorization;

    /**
     * @param User $user
     * @param PassbackByAssignment $passbackByAssignment
     * @param Assignment $assignment
     * @return Response
     */
    public function store(User       $user, PassbackByAssignment $passbackByAssignment,
                          Assignment $assignment): Response
    {
        return $assignment->course->user_id === $user->id
            ? Response::allow()
            : Response::deny('You are not allowed to passback the grades by assignment.');

    }
}
