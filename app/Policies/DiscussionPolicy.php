<?php

namespace App\Policies;

use App\Assignment;
use App\Discussion;
use App\Question;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use App\Traits\GeneralSubmissionPolicy;

class DiscussionPolicy
{
    use HandlesAuthorization;
    use GeneralSubmissionPolicy;

    /**
     * @param User $user
     * @param Discussion $discussion
     * @param Assignment $assignment
     * @return Response
     */
    public function show(User $user, Discussion $discussion, Assignment $assignment): Response
    {

        switch ($user->role) {
            case(3):
                $has_access = $assignment->course->enrollments->contains('user_id', $user->id);
                break;
            case(2):
                $has_access = $assignment->course->user_id === $user->id;
                break;
            default:
                $has_access = false;
        }
        return $has_access
            ? Response::allow()
            : Response::deny('You are not allowed to view the discussions for this question.');
    }

    /**
     * @param User $user
     * @param Discussion $discussion
     * @param Assignment $assignment
     * @param Question $question
     * @return Response
     */
    public function store(User $user, Discussion $discussion, Assignment $assignment, Question $question): Response
    {
        $message = "You are not allowed to create a discussion for that assignment.";
        switch ($user->role) {
            case(3):
                $has_access = true;
                $general_submission_policy = $this->canSubmitBasedOnGeneralSubmissionPolicy($user, $assignment, $assignment->id, $question->id);
                if ($general_submission_policy['type'] === 'error') {
                    $has_access = false;
                    $message = $general_submission_policy['message'];
                }
                break;
            case(2):
                $has_access = $assignment->course->user_id === $user->id;
                break;
            default:
                $has_access = false;

        }
        return $has_access
            ? Response::allow()
            : Response::deny($message);


    }


}
