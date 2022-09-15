<?php

namespace App\Policies;

use App\Assignment;
use App\AssignmentQuestionTimeSpent;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class AssignmentQuestionTimeSpentPolicy
{
    use HandlesAuthorization;

    public function getTimeSpentsByAssignment(User $user, AssignmentQuestionTimeSpent $assignmentQuestionTimeSpent, Assignment $assignment): Response
    {
        return $assignment->course->user_id === $user->id
            ? Response::allow()
            : Response::deny('You are not allowed to get the time spent on each question for this assignment.');
    }



}
