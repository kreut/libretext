<?php

namespace App\Policies;

use App\Assignment;
use App\Question;
use App\QuestionRevision;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class QuestionRevisionPolicy
{
    use HandlesAuthorization;

    /**
     * @param User $user
     * @return Response
     */
    public function getUpdateRevisionInfo(User $user): Response
    {
        return in_array($user->role,[2,5])
            ? Response::allow()
            : Response::deny("You are not allowed to get the updated revision information.");

    }

    /**
     * @param User $user
     * @param QuestionRevision $questionRevision
     * @return Response
     */
    public function show(User $user, QuestionRevision $questionRevision): Response
    {

        return in_array($user->role,[2,5])
            ? Response::allow()
            : Response::deny("You are not allowed to get this question revision.");
    }

    /**
     * @param User $user
     * @param QuestionRevision $questionRevision
     * @param Question $question
     * @return Response
     */
    public function index(User $user, QuestionRevision $questionRevision, Question $question): Response
    {
        return in_array($user->role,[2,5])
            ? Response::allow()
            : Response::deny("You are not allowed to get the revisions for this question.");

    }



    /**
     * @param User $user
     * @param QuestionRevision $questionRevision
     * @param Assignment $assignment
     * @return Response
     */
    public function emailStudentsWithSubmissions(User $user, QuestionRevision $questionRevision, Assignment $assignment): Response {
        return $assignment->course->user_id === $user->id
            ? Response::allow()
            : Response::deny("You are not allowed to email these students with submissions.");

    }
}
