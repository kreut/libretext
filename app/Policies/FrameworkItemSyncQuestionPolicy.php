<?php

namespace App\Policies;

use App\FrameworkItemSyncQuestion;
use App\User;
use Exception;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\DB;

class FrameworkItemSyncQuestionPolicy
{
    use HandlesAuthorization;

    public function getQuestionsByDescriptor(User $user): Response
    {
        return in_array($user->role, [2, 4, 5])
            ? Response::allow()
            : Response::deny('You are not allowed to get the descriptors for that question.');

    }
    /**
     * @param User $user
     * @return Response
     */
    public function getFrameworkItemsByQuestion(User $user): Response
    {
        return in_array($user->role, [2, 4, 5])
            ? Response::allow()
            : Response::deny('You are not allowed to get the framework alignments for the question.');

    }

    /**
     * @throws Exception
     */
    public function sync(User                      $user,
                         FrameworkItemSyncQuestion $frameworkItemSyncQuestion,
                         int                       $question_editor_user_id): Response
    {

        return $question_editor_user_id === $user->id
            ? Response::allow()
            : Response::deny('You are not allowed to sync framework alignments to that question.');

    }
}
