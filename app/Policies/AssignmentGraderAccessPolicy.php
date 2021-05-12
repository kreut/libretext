<?php

namespace App\Policies;

use App\Assignment;
use App\AssignmentGraderAccess;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class AssignmentGraderAccessPolicy
{
    use HandlesAuthorization;

    public function index(User $user, AssignmentGraderAccess $assignmentGraderAccess, Assignment $assignment): Response
    {
        return (int) $assignment->course->user_id === $user->id
            ? Response::allow()
            : Response::deny('You are not allowed to view the grader access for this assignment.');

    }

    /**
     * @param User $user
     * @param AssignmentGraderAccess $assignmentGraderAccess
     * @param Assignment $assignment
     * @return Response
     */
    public function assignmentAccessForGraders(User $user, AssignmentGraderAccess $assignmentGraderAccess, Assignment $assignment): Response
    {

        return (int) $assignment->course->user_id === $user->id
            ? Response::allow()
            : Response::deny('You are not allowed to give graders access to this assignment.');

    }

    /**
     * @param User $user
     * @param AssignmentGraderAccess $assignmentGraderAccess
     * @param Assignment $assignment
     * @param User $grader
     * @return Response
     */
    public function assignmentAccessForGrader(User $user,
                                              AssignmentGraderAccess $assignmentGraderAccess,
                                              Assignment $assignment, User $grader): Response
    {
        return (int) $assignment->course->user_id === $user->id && in_array($grader->id,$assignment->course->graders()->pluck('id')->toArray())
            ? Response::allow()
            : Response::deny('You are not allowed to give this grader access to this assignment.');

    }

}
