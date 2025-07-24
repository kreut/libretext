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

    public function view(User $user, Cutup $cutup){
        return (int) $user->role === 2
            ? Response::allow()
            : Response::deny('You are not allowed to retrieve cutups for this assignment.');

    }
    public function updateSolution(User $user, Cutup $cutup, Assignment $assignment, Question $question)
    {


        if (!in_array($question->id, $assignment->questions->pluck('id')->toArray())) {
            return Response::deny('That question is not in the assignment.');
        }

        $has_access = $assignment->course->ownsCourseOrIsCoInstructor($user->id);

        return $has_access
            ? Response::allow()
            : Response::deny('You are not allowed to create a cutup for this assignment.');

    }
}
