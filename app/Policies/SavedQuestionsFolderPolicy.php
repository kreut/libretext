<?php

namespace App\Policies;

use App\SavedQuestionsFolder;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class SavedQuestionsFolderPolicy
{
    use HandlesAuthorization;

    public function store(User $user, SavedQuestionsFolder $savedQuestionFolder): Response
    {

        return ($user->role === 2)
            ? Response::allow()
            : Response::deny("You are not allowed to create folders.");

    }

    public function update(User $user, SavedQuestionsFolder $savedQuestionFolder): Response
    {

        return ($savedQuestionFolder->user_id === $user->id)
            ? Response::allow()
            : Response::deny("You are not allowed to update this folder.");

    }

    public function destroy(User $user, SavedQuestionsFolder $savedQuestionFolder): Response
    {

        return ($user->id ===  $savedQuestionFolder->user_id)
            ? Response::allow()
            : Response::deny("You are not allowed to delete that folder.");

    }

}
