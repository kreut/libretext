<?php

namespace App\Policies;

use App\User;
use App\Question;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\DB;


class QuestionPolicy
{
    use HandlesAuthorization;


    public function viewAny(User $user)
    {
        return ($user->role !== 3)
            ? Response::allow()
            : Response::deny('You are not allowed to retrieve the questions from the database.');

    }

    public function viewByPageId(User $user, Question $question, int $page_id)
    {
        switch ($user->role) {
            case(2):
            case(4):
                $has_access = true;
                break;
            case(3):
                $has_access = DB::table('assignment_question')
                    ->join('questions', 'assignment_question.question_id', '=', 'questions.id')
                    ->join('assignments', 'assignment_question.assignment_id', '=', 'assignments.id')
                    ->join('enrollments', 'assignments.course_id', '=', 'enrollments.course_id')
                    ->where('enrollments.user_id', $user->id)
                    ->where('questions.page_id', $page_id)
                    ->where('enrollments.user_id', $user->id)
                    ->select('questions.id')
                    ->get()
                    ->isNotEmpty();
                break;
            default:
                $has_access = false;
        }
        return $has_access
            ? Response::allow()
            : Response::deny('You are not allowed to view this non-technology question.');

    }


}

