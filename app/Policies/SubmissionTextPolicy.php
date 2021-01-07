<?php

namespace App\Policies;

use App\Assignment;
use App\SubmissionText;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class SubmissionTextPolicy
{
    use HandlesAuthorization;

    public function storeSubmissionText(User $user, SubmissionText $submissionText, Assignment $assignment)
    {

        return $assignment->course->enrollments->contains('user_id', $user->id)
            ? Response::allow()
            : Response::deny('You are not allowed to submit text to this assignment.');

    }
}
