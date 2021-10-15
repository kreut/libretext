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


    public function index(User $user, SubmissionOverride $submissionOverride, Assignment $assignment)
    {
        return (int)$assignment->course->user_id === (int)$user->id
            ? Response::allow()
            : Response::deny("You are not allowed to view the overrides for this assignment.");
    }

    public function updateCompiledPDF(User $user, SubmissionOverride $submissionOverride, Assignment $assignment)
    {
        return (int)$assignment->course->user_id === (int)$user->id
            ? Response::allow()
            : Response::deny("You are not allowed to update the overrides for this assignment.");
    }

    public function updateQuestionLevel(User               $user,
                                        SubmissionOverride $submissionOverride,
                                        Assignment         $assignment,
                                        int                $question_id)
    {
        return $assignment->questions->contains($question_id) && (int)$assignment->course->user_id === (int)$user->id
            ? Response::allow()
            : Response::deny("You are not allowed to update the overrides for that combination of assignments and questions.");
    }

    public function deleteCompiledPDF(User $user, SubmissionOverride $submissionOverride, Assignment $assignment)
    {
        return (int)$assignment->course->user_id === (int)$user->id
            ? Response::allow()
            : Response::deny("You are not allowed to delete the overrides for this assignment.");
    }

    public function deleteQuestionLevel(User               $user,
                                        SubmissionOverride $submissionOverride,
                                        Assignment         $assignment,
                                        int                $question_id)
    {
        return $assignment->questions->contains($question_id) && (int)$assignment->course->user_id === (int)$user->id
            ? Response::allow()
            : Response::deny("You are not allowed to delete that override.");

    }
}
