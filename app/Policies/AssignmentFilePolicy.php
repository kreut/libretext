<?php

namespace App\Policies;

use App\User;
use App\Assignment;
use App\AssignmentFile;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class AssignmentFilePolicy
{
    use HandlesAuthorization;

    public function uploadAssignmentFile(User $user, AssignmentFile $assignmentFile, Assignment $assignment) {

        return  $assignment->course->enrollments->contains('user_id', $user->id)
            ? Response::allow()
            : Response::deny('You are not allowed to access this assignment.');

    }
}
