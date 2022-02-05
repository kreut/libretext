<?php

namespace App\Policies;

use App\Assignment;
use App\Question;
use App\Solution;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class SolutionPolicy
{
    use HandlesAuthorization;

    public function showSolutionByAssignmentQuestionUser(User $user, Solution $solution, Assignment $assignment, Question $question)
    {
        $authorized = true;
        $message = '';
        $submission_exists = DB::table('submissions')
            ->where('assignment_id', $assignment->id)
            ->where('question_id', $question->id)
            ->where('user_id', $user->id)
            ->first();
        $can_give_up_exists = DB::table('can_give_ups')
            ->where('assignment_id', $assignment->id)
            ->where('question_id', $question->id)
            ->where('user_id', $user->id)
            ->where('status', 'can give up')
            ->first();

        if (!($submission_exists || $can_give_up_exists)) {
            $authorized = false;
            $message = 'Please submit at least once before looking at the solution.';
        }
        if ($submission_exists  && !($assignment->assessment_type === 'real time' && $assignment->number_of_allowed_attempts === 'unlimited')) {
            $authorized = false;
            $message = "You cannot view the solutions since this is not real time with unlimited attempts.";
        }

        return $authorized
            ? Response::allow()
            : Response::deny($message);
    }

    public function uploadSolutionFile(User $user)
    {
        return (int)$user->role === 2
            ? Response::allow()
            : Response::deny('You are not allowed to upload solutions.');

    }

    public function destroy(User $user, Solution $solution, Assignment $assignment, Question $question)
    {
        $owns_solution = $solution->where('question_id', $question->id)
            ->where('user_id', $user->id)
            ->first();
        return $owns_solution
            ? Response::allow()
            : Response::deny('You are not allowed to remove this solution.');


    }

    public function storeText(User $user, Solution $solution, Assignment $assignment, Question $question)
    {
        return $assignment->questions->contains($question->id) && (int)$assignment->course->user_id === $user->id
            ? Response::allow()
            : Response::deny('You are not allowed to submit a text-based solution to this question.');

    }

    public function downloadSolutionFile(User $user, Solution $solution, string $level, Assignment $assignment, $question_id)
    {
//$question_id will be null if it's at the assignment level

        if ((int)$user->role === 3 && !$assignment->solutions_released) {
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
