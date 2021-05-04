<?php

namespace App\Policies;

use App\Assignment;
use App\Question;
use App\Solution;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;


class SolutionPolicy
{
    use HandlesAuthorization;


    public function uploadSolutionFile(User $user)
    {
        return (int) $user->role === 2
            ? Response::allow()
            : Response::deny('You are not allowed to upload solutions.');

    }

    public function storeText(User $user, Solution $solution, Assignment $assignment, Question $question)
    {
        return $assignment->questions->contains($question->id) && (int) $assignment->course->user_id === $user->id
            ? Response::allow()
            : Response::deny('You are not allowed to submit a text-based solution to this question.');

    }

    public function downloadSolutionFile(User $user, Solution $solution, string $level, Assignment $assignment,  $question_id)
    {
//$question_id will be null if it's at the assignment level

        if ((int) $user->role === 3 && !$assignment->solutions_released) {
            return Response::deny("The solutions are not released so you can't download the solution.");
        }


        if ($level === 'q' && !$assignment->questions->contains($question_id)) {

            return Response::deny('That question is not part of the assignment so you cannot download the solutions.');
        }

        switch (Auth::user()->role) {
            case(2):
                $has_access = (int)$assignment->course->user_id === (int)Auth::user()->id;
                break;
            case(3):
                $has_access = $assignment->course->enrollments->contains('user_id', Auth::user()->id);
                break;
            case(4):
                $has_access = $assignment->course->isGrader();
                break;
            default:
                $has_access = false;
        }

        return $has_access
            ? Response::allow()
            : Response::deny('You are not allowed to download these solutions.');

    }
}
