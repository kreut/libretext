<?php

namespace App\Policies;

use App\Assignment;

use App\AssignmentSyncQuestion;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class AssignmentSyncQuestionPolicy
{
    use HandlesAuthorization;


    /**
     * Determine whether the user can delete the question in the assignment.
     *
     * @param \App\User $user
     * @param \App\Assignment $assignment
     * @return mixed
     */
    public function delete(User $user, AssignmentSyncQuestion $assignmentSyncQuestion, Assignment $assignment)
    {
        $authorized = (!$assignment->hasFileOrQuestionSubmissions()) && ($user->id === ((int)$assignment->course->user_id));
        $message = ($assignment->hasFileOrQuestionSubmissions())
            ? "You can't remove a question from this assignment since students have already submitted responses."
            : 'You are not allowed to remove a question from this assignment.';
        return $authorized
            ? Response::allow()
            : Response::deny($message);
    }

    /**
     * Determine whether the user can add the question to the assignment.
     *
     * @param \App\User $user
     * @param \App\Assignment $assignment
     * @return mixed
     */
    public function add(User $user, AssignmentSyncQuestion $assignmentSyncQuestion, Assignment $assignment)
    {
        $authorized = (!$assignment->hasFileOrQuestionSubmissions()) && ($user->id === ((int)$assignment->course->user_id));
        $message = ($assignment->hasFileOrQuestionSubmissions())
            ? "You can't add a question to this assignment since students have already submitted responses."
            : 'You are not allowed to add a question to this assignment.';
        return $authorized
            ? Response::allow()
            : Response::deny($message);
    }

    public function update(User $user, AssignmentSyncQuestion $assignmentSyncQuestion, Assignment $assignment)
    {
        $authorized = (!$assignment->hasFileOrQuestionSubmissions()) && ($user->id === ((int)$assignment->course->user_id));
        $message = '';
        if (!$authorized){
            $message= $assignment->hasFileOrQuestionSubmissions()
                ? "This cannot be updated since students have already submitted responses."
                : "You are not allowed to update that resource.";
        }
        return $authorized
            ? Response::allow()
            : Response::deny($message);
    }

    /**
     * @param User $user
     * @param AssignmentSyncQuestion $assignmentSyncQuestion
     * @param Assignment $assignment
     * @return Response
     */
    public function updateOpenEndedSubmissionType(User $user, AssignmentSyncQuestion $assignmentSyncQuestion, Assignment $assignment)
    {

        return $user->id === ((int)$assignment->course->user_id)
            ? Response::allow()
            : Response::deny("You are not allowed to update the open ended submission type.");
    }

    /**
     * @param User $user
     * @param AssignmentSyncQuestion $assignmentSyncQuestion
     * @param Assignment $assignment
     * @return Response
     */
    public function updateQuestionAccessLevel(User $user, AssignmentSyncQuestion $assignmentSyncQuestion, Assignment $assignment)
    {

        return $user->id === ((int)$assignment->course->user_id)
            ? Response::allow()
            : Response::deny("You are not allowed to update the question access level.");
    }





}
