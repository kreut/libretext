<?php

namespace App\Policies;

use App\Assignment;
use App\AssignmentQuestionCaseStudyNotes;
use App\Question;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class AssignmentQuestionCaseStudyNotesPolicy
{
    use HandlesAuthorization;

    /**
     * @param User $user
     * @param AssignmentQuestionCaseStudyNotes $assignmentQuestionCaseStudyNotes
     * @param Question $question
     * @return Response
     */
    public function index(User                             $user,
                          AssignmentQuestionCaseStudyNotes $assignmentQuestionCaseStudyNotes,
                          Question                     $question): Response
    {
        switch ($user->role) {
            case(2):
            case(4):
            case(5):
                $has_access = true;
                break;
            case(3):
                $has_access = $question->canBeViewedByStudent($user);
                break;
            default:
                $has_access = false;
        }
        return $has_access
            ? Response::allow()
            : Response::deny('You are not allowed to get theses Case Study Notes.');

    }

    public function update(User                             $user,
                          AssignmentQuestionCaseStudyNotes $assignmentQuestionCaseStudyNotes,
                          Assignment                       $assignment): Response
    {

        return $assignment->course->user_id === $user->id
            ? Response::allow()
            : Response::deny('You are not allowed to update theses Case Study Notes.');

    }
}
