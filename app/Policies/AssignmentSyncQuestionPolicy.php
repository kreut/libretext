<?php

namespace App\Policies;

use App\Assignment;

use App\AssignmentSyncQuestion;
use App\SubmissionFile;
use App\User;
use App\Question;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\DB;

class AssignmentSyncQuestionPolicy
{
    use HandlesAuthorization;


    /**
     * @param User $user
     * @param AssignmentSyncQuestion $assignmentSyncQuestion
     * @param Assignment $assignment
     * @param Question $question
     * @return Response
     */
    public function updateIFrameProperties(User $user,
                                           AssignmentSyncQuestion $assignmentSyncQuestion,
                                           Assignment $assignment,
                                           Question $question)
    {

        return (int) $user->id === $assignment->course->user_id && in_array($question->id, $assignment->questions->pluck('id')->toArray())
            ? Response::allow()
            : Response::deny('You are not allowed to update the iframe properties for that question.');


    }

    public function remixAssignmentWithChosenQuestions(User $user,
                                                       AssignmentSyncQuestion $assignmentSyncQuestion,
                                                       Assignment             $assignment)
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
    public function add(User                   $user,
                        AssignmentSyncQuestion $assignmentSyncQuestion,
                        Assignment             $assignment)
    {


        return ($user->id === (int)$assignment->course->user_id)
            ? Response::allow()
            : Response::deny('You are not allowed to add a question to this assignment.');
    }

    public function update(User $user, AssignmentSyncQuestion $assignmentSyncQuestion, Assignment $assignment)
    {
        $authorized = true;
        $message = '';

        /*** IMPORTANT: What will you do about the update points business with the randomized questions??? ***/
        if (($user->id !== ((int)$assignment->course->user_id))) {
            $message = "You are not allowed to update that resource.";
            $authorized = false;
        } else if ($assignment->course->alpha
            && $assignment->hasNonFakeStudentFileOrQuestionSubmissions($assignment->addBetaAssignmentIds())) {
            {
                $message = "There is at least one submission to this question in one of the Beta assignments so you can't change the points.";
                $authorized = false;
            }
        } else if ($assignment->isBetaAssignment()) {
            $message = "This is an assignment in a Beta course so you can't change the points.";
            $authorized = false;
        } else if ($assignment->hasNonFakeStudentFileOrQuestionSubmissions()) {
            $authorized = false;
            $message = "This cannot be updated since students have already submitted responses to this assignment.";
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
    public function hasNonScoredSubmissionFiles(User                   $user,
                                                AssignmentSyncQuestion $assignmentSyncQuestion,
                                                Assignment             $assignment){

        return $assignment->course->id
            ? Response::allow()
            : Response::deny("You are not allowed to check for non-scored submission files.");

    }

    /**
     * @param User $user
     * @param AssignmentSyncQuestion $assignmentSyncQuestion
     * @param Assignment $assignment
     * @param Question $question
     * @param SubmissionFile $submissionFile
     * @return Response
     */
    public function updateOpenEndedSubmissionType(User                   $user,
                                                  AssignmentSyncQuestion $assignmentSyncQuestion,
                                                  Assignment             $assignment,
                                                  Question               $question,
                                                  SubmissionFile         $submissionFile): Response
    {
        $message = '';
        $authorized = true;
        if (($user->id !== ((int)$assignment->course->user_id))) {
            $message = "You are not allowed to update the open-ended submission type.";
            $authorized = false;
        } else if ($assignment->course->alpha
            && $submissionFile->hasNonFakeStudentFileSubmissionsForAssignmentQuestion($assignment->addBetaAssignmentIds(), $question->id, true)) {
            {
                $message = "There is at least one graded submission to this question in either the Alpha assignment or one of the Beta assignments so you can't change the open-ended submission type.";
                $authorized = false;
            }
        } else if ($assignment->isBetaAssignment()) {
            $message = "This is an assignment in a Beta course so you can't change the open-ended submission type.";
            $authorized = false;
        } else if ($submissionFile->hasNonFakeStudentFileSubmissionsForAssignmentQuestion([$assignment->id], $question->id,true)) {
            $authorized = false;
            $message = "There is at least one graded submission to this question so you can't change the open-ended submission type.";
        }

        return $authorized
            ? Response::allow()
            : Response::deny($message);
    }

    public function updateClickerResultsReleased(User $user, AssignmentSyncQuestion $assignmentSyncQuestion, Assignment $assignment)
    {

        return $user->id === ((int)$assignment->course->user_id)
            ? Response::allow()
            : Response::deny("You are not allowed to update the clicker status for this question.");
    }


}
