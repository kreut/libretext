<?php

namespace App\Policies;

use App\Question;
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

    /**
     * @param User $user
     * @param QuestionMediaUpload $questionMediaUpload
     * @param string $s3_key
     * @return Response
     */
    public function updateText(User $user, QuestionMediaUpload $questionMediaUpload, string $s3_key): Response
    {
        $question_media_upload = $questionMediaUpload->where('s3_key', $s3_key)->first();
        $question = null;
        if ($question_media_upload) {
            $question = Question::find($question_media_upload->question_id);
        }
        return  $question && $user->id === $question->question_editor_user_id || !$question
            ? Response::allow()
            : Response::deny("You are not allowed to update the text for this question media upload.");

    }


    /**
     * @param User $user
     * @return Response
     */
    public function storeText(User $user): Response
    {
        return $user->role === 2
            ? Response::allow()
            : Response::deny("You are not allowed to store text as a question media upload.");

    }

    /**
     * @param User $user
     * @param QuestionMediaUpload $questionMediaUpload
     * @return Response
     */
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
    public function temporaryUrls(User $user, QuestionMediaUpload $questionMediaUpload): Response
    {
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
