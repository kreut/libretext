<?php

namespace App\Policies;

use App\QuestionMediaUpload;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class QuestionMediaUploadPolicy
{
    use HandlesAuthorization;

    /**
     * @param QuestionMediaUpload $questionMediaUpload
     * @param User $user
     * @return mixed
     */
    private function _ownsQuestionMediaUpload(QuestionMediaUpload $questionMediaUpload, User $user)
    {
        return $questionMediaUpload->join('questions', 'question_media_uploads.question_id', '=', 'questions.id')
            ->where('question_media_uploads.id', $questionMediaUpload->id)
            ->where('question_editor_user_id', $user->id)
            ->first();
    }

    public function destroy(User $user, QuestionMediaUpload $questionMediaUpload): Response
    {
        return $this->_ownsQuestionMediaUpload($questionMediaUpload, $user)
            ? Response::allow()
            : Response::deny("You are not allowed to delete this question media.");


    }

    public function updateCaption(User $user, QuestionMediaUpload $questionMediaUpload): Response
    {
        return $this->_ownsQuestionMediaUpload($questionMediaUpload, $user)
            ? Response::allow()
            : Response::deny("You are not allowed to update this transcript.");

    }
}
