<?php

namespace App\Policies;


use App\CurrentQuestionEditor;
use App\Question;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\DB;

class CurrentQuestionEditorPolicy
{
    use HandlesAuthorization;

    /**
     * @param User $user
     * @return Response
     */
    public function update(User $user): Response
    {
        return (int)$user->role === 5
            ? Response::allow()
            : Response::deny('You are not allowed to update the current question editor.');

    }

    /**
     * @param User $user
     * @return Response
     */
    public function show(User $user): Response
    {
        return (int)$user->role === 5
            ? Response::allow()
            : Response::deny('You are not allowed to view the current question editor.');

    }

    public function destroy(User $user, CurrentQuestionEditor $currentQuestionEditor, Question $question): Response
    {
        $is_currently_editing = DB::table('current_question_editors')
            ->where('user_id', $user->id)
            ->where('question_id', $question->id)
            ->first();
        return $is_currently_editing
            ? Response::allow()
            : Response::deny('You are not allowed to remove the current question editor.');

    }
}
