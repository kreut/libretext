<?php

namespace App\Policies;

use App\Assignment;
use App\SubmissionScoreOverride;
use App\SubmissionText;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class SubmissionScoreOverridePolicy
{
    use HandlesAuthorization;

    /**
     * @param User $user
     * @param SubmissionScoreOverride $submissionScoreOverride
     * @param int $assignment_id
     * @param int $student_user_id
     * @return Response
     */
    public function update(User                    $user,
                           SubmissionScoreOverride $submissionScoreOverride,
                           int                     $assignment_id,
                           int                     $student_user_id): Response
    {

        $has_access = true;
        $message = '';
        $assignment = Assignment::find($assignment_id);
        if ($assignment->course->user_id !== $user->id) {
            $has_access = false;
            $message = "The assignment is not in your course.";
        } else if (!$assignment->course->enrollments->contains('user_id', $student_user_id)) {
            $has_access = false;
            $message = "That student is not enrolled in your course.";
        }
        return $has_access
            ? Response::allow()
            : Response::deny($message);

    }
}
