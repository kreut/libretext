<?php

namespace App\Policies;

use App\User;
use App\Cutup;
use App\Assignment;
use App\Question;
use Illuminate\Auth\Access\HandlesAuthorization;
use \App\Traits\CommonPolicies;
use Illuminate\Auth\Access\Response;

class CutupPolicy
{
    use HandlesAuthorization;
    use CommonPolicies;

    public function setAsSolutionOrSubmission(User $user, Cutup $cutup, Assignment $assignment, Question $question)
    {
        $has_access = false;

        if (!in_array($question->id, $assignment->questions->pluck('id')->toArray())){
            return Response::deny('That question is not in the assignment.');
        }
        switch ($user->role) {
            case(2):
                $has_access = $this->ownsCourseByUser($assignment->course, $user);
                break;
            case(3):
                $has_access = $assignment->course->enrollments->contains('user_id', $user->id);
                break;
            default:
                false;
        }
        return $has_access
            ? Response::allow()
            : Response::deny('You are not allowed to create a cutup for this assignment.');

    }
}
