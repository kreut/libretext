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
    public function download(User $user, QuestionMediaUpload $questionMediaUpload): Response
    {
        return $this->_ownsQuestionMediaUpload($questionMediaUpload, $user)
            ? Response::allow()
            : Response::deny("You are not allowed to download the transcript.");


    }

    /**
     * @param User $user
     * @param QuestionMediaUpload $questionMediaUpload
     * @return Response
     */
    public function temporaryUrls(User $user, QuestionMediaUpload $questionMediaUpload): Response {
        return $user->role === 2
            ? Response::allow()
            : Response::deny("You are not allowed to get the temporary URL for the question media upload.");
    }

    public function validateVTT(User $user, QuestionMediaUpload $questionMediaUpload): Response
    {
        return $this->_ownsQuestionMediaUpload($questionMediaUpload, $user)
            ? Response::allow()
            : Response::deny("You are not allowed to validate the .vtt file.");


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
