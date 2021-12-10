<?php

namespace App\Policies;

use App\Helpers\Helper;
use App\QuestionEditor;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class QuestionEditorPolicy
{
    use HandlesAuthorization;

    public function index(User $user): Response
    {

        return Helper::isAdmin()
            ? Response::allow()
            : Response::deny('You are not allowed to get the question editors.');

    }

    public function destroy(User $user, QuestionEditor $questionEditor, User $question_editor_user): Response
    {
        $message = '';
        $authorize = true;
        if (!Helper::isAdmin()) {
            $message = "You are not allowed to delete that user.";
            $authorize = false;
        }

        if ($question_editor_user->role !== 5) {
            $message = "That user is not a question editor.";
            $authorize = false;
        }
        if ($question_editor_user->id === Helper::defaultNonInstructorEditor()->id) {
            $message = "You cannot delete the default non-instructor editor.";
            $authorize = false;
        }
        return $authorize
            ? Response::allow()
            : Response::deny($message);

    }
}
