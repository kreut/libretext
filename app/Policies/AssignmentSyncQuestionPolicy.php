<?php

namespace App\Policies;

use App\Assignment;

use App\AssignmentSyncQuestion;
use App\User;
use App\Question;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class AssignmentSyncQuestionPolicy
{
    use HandlesAuthorization;

    public function remixAssignmentWithChosenQuestions(User $user,
                                                       AssignmentSyncQuestion $assignmentSyncQuestion,
                                                       Assignment $assignment)
    {

        return (int)$user->id === $assignment->course->user_id
            ? Response::allow()
            : Response::deny('You are not allowed to remix that assignment.');


    }

    public function order(User $user, AssignmentSyncQuestion $assignmentSyncQuestion, Assignment $assignment)
    {

        return (int)$assignment->course->user_id === $user->id
            ? Response::allow()
            : Response::deny('You are not allowed to order the questions for this assignment.');

    }

    public function storeOpenEndedSubmissionDefaultText(User $user, AssignmentSyncQuestion $assignmentSyncQuestion, Assignment $assignment, Question $question)
    {
        $authorized = $assignment->questions->contains($question->id) && ($user->id === ((int)$assignment->course->user_id));
        $message = (!$assignment->questions->contains($question->id))
            ? "You can't add default text to that  question since it's not in the assignment."
            : 'You are not allowed to add default text to this assignment.';
        return $authorized
            ? Response::allow()
            : Response::deny($message);

    }

    /**
     * @param User $user
     * @param AssignmentSyncQuestion $assignmentSyncQuestion
     * @param Assignment $assignment
     * @param Question $question
     * @return Response
     */
    public function delete(User $user, AssignmentSyncQuestion $assignmentSyncQuestion, Assignment $assignment, Question $question)
    {
        $authorized = $assignment->questions->contains($question->id) && ($user->id === ((int)$assignment->course->user_id));
        $message = (!$assignment->questions->contains($question->id))
            ? "You can't remove that question since it's not in the assignment."
            : 'You are not allowed to remove a question from this assignment.';
        return $authorized
            ? Response::allow()
            : Response::deny($message);
    }

    public function startClickerAssessment(User $user, AssignmentSyncQuestion $assignmentSyncQuestion, Assignment $assignment, Question $question)
    {
        $authorized = $assignment->questions->contains($question->id) && ($user->id === ((int)$assignment->course->user_id));
        return $authorized
            ? Response::allow()
            : Response::deny('You are not allowed to start this clicker assessment.');
    }

    /**
     * @param User $user
     * @param AssignmentSyncQuestion $assignmentSyncQuestion
     * @param Assignment $assignment
     * @param Question $question
     * @return Response
     */
    public function add(User $user,
                        AssignmentSyncQuestion $assignmentSyncQuestion,
                        Assignment $assignment)
    {


        return ($user->id === (int)$assignment->course->user_id)
            ? Response::allow()
            : Response::deny('You are not allowed to add a question to this assignment.');
    }

    public function update(User $user, AssignmentSyncQuestion $assignmentSyncQuestion, Assignment $assignment)
    {
        $authorized = (!$assignment->hasFileOrQuestionSubmissions()) && ($user->id === ((int)$assignment->course->user_id));
        $message = '';
        if (!$authorized) {
            $message = $assignment->hasFileOrQuestionSubmissions()
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

    public function updateClickerResultsReleased(User $user, AssignmentSyncQuestion $assignmentSyncQuestion, Assignment $assignment)
    {

        return $user->id === ((int)$assignment->course->user_id)
            ? Response::allow()
            : Response::deny("You are not allowed to update the clicker status for this question.");
    }


}
