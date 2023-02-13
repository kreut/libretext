<?php

namespace App\Policies;

use App\Assignment;
use App\Question;
use App\SubmissionOverride;
use App\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Auth\Access\HandlesAuthorization;

class SubmissionOverridePolicy
{
    use HandlesAuthorization;

    /**
     * @param $user
     * @param $assignment
     * @return bool
     */
    private function _submissionOverrideAccess($user, $assignment): bool
    {
        $grader_access = false;
        foreach ($assignment->gradersAccess() as $value) {
            if ($value['user_id'] === $user->id) {
                $grader_access = true;
            }
        }
        return $grader_access || $assignment->course->user_id === $user->id;
    }
    /**
     * @param User $user
     * @param SubmissionOverride $submissionOverride
     * @param Assignment $assignment
     * @return Response
     */
    public function index(User $user, SubmissionOverride $submissionOverride, Assignment $assignment): Response
    {

        return $this->_submissionOverrideAccess($user, $assignment)
            ? Response::allow()
            : Response::deny("You are not allowed to view the overrides for this assignment.");
    }

    /**
     * @param User $user
     * @param SubmissionOverride $submissionOverride
     * @param Assignment $assignment
     * @return Response
     */
    public function updateAssignmentLevel(User $user, SubmissionOverride $submissionOverride, Assignment $assignment): Response
    {

        return $this->_submissionOverrideAccess($user, $assignment)
            ? Response::allow()
            : Response::deny("You are not allowed to update the overrides for this assignment.");
    }

    /**
     * @param User $user
     * @param SubmissionOverride $submissionOverride
     * @param Assignment $assignment
     * @param int $question_id
     * @return Response
     */
    public function updateQuestionLevel(User               $user,
                                        SubmissionOverride $submissionOverride,
                                        Assignment         $assignment,
                                        int                $question_id): Response
    {
        return $assignment->questions->contains($question_id) && $this->_submissionOverrideAccess($user, $assignment)
            ? Response::allow()
            : Response::deny("You are not allowed to update the overrides for that combination of assignments and questions.");
    }

    /**
     * @param User $user
     * @param SubmissionOverride $submissionOverride
     * @param Assignment $assignment
     * @return Response
     */
    public function deleteAssignmentLevel(User $user, SubmissionOverride $submissionOverride, Assignment $assignment): Response
    {
        return $this->_submissionOverrideAccess($user, $assignment)
            ? Response::allow()
            : Response::deny("You are not allowed to delete the overrides for this assignment.");
    }

    /**
     * @param User $user
     * @param SubmissionOverride $submissionOverride
     * @param Assignment $assignment
     * @param int $question_id
     * @return Response
     */
    public function deleteQuestionLevel(User               $user,
                                        SubmissionOverride $submissionOverride,
                                        Assignment         $assignment,
                                        int                $question_id): Response
    {
        return $assignment->questions->contains($question_id) && $this->_submissionOverrideAccess($user, $assignment)
            ? Response::allow()
            : Response::deny("You are not allowed to delete that override.");

    }
}
