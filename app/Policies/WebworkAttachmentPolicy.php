<?php

namespace App\Policies;

use App\Helpers\Helper;
use App\Question;
use App\User;
use App\WebworkAttachment;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\DB;

class WebworkAttachmentPolicy
{
    use HandlesAuthorization;

    /**
     * @param User $user
     * @param WebworkAttachment $webworkAttachment
     * @param Question $question
     * @param string $action
     * @return Response
     */
    public function actOnWebworkAttachmentByQuestion(User $user, WebworkAttachment $webworkAttachment, Question $question, string $action): Response
    {
        $message = 'Unknown authorization user to update question';
        if (Helper::isAdmin()) {
            $authorize = true;
        } else if ($user->role === 5) {
            $authorize = true;
            $question_editor = User::find($question->question_editor_user_id);
            if ($question_editor->role !== 5) {
                $authorize = false;
                $message = "You are a non-instructor editor but the question was created by someone who is not a non-instructor editor.  You are not allowed to $action the question's attachments.";
            }
        } else {
            $authorize = $user->isDeveloper() || Helper::isAdmin() || ((int)$user->id == $question->question_editor_user_id
                    && ($user->role === 2));
            if (!$authorize) {
                if ((int)$user->id !== $question->question_editor_user_id) {
                    $user = User::find($question->question_editor_user_id);
                    $message = "This is not your question to edit. This question is owned by $user->first_name $user->last_name.";
                } else {
                    $message = "You are not allowed to $action the question's attachments.";
                }
            }
        }
        return $authorize
            ? Response::allow()
            : Response::deny($message);

    }

    /**
     * @param User $user
     * @return Response
     */
    public function upload(User $user): Response
    {
        return in_array($user->role, [2, 5])
            ? Response::allow()
            : Response::deny("You are not allowed to upload webwork attachments.");
    }
}
