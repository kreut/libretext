<?php

namespace App\Policies;

use App\MyFavorite;
use App\Question;
use App\SavedQuestionsFolder;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\DB;

class SavedQuestionsFolderPolicy
{
    use HandlesAuthorization;



    public function getMyQuestionsFoldersAsOptions(User $user, SavedQuestionsFolder $savedQuestionFolder): Response
    {

        return (in_array($user->role,[2,5]))
            ? Response::allow()
            : Response::deny("You are not allowed to retrieve the My Questions folders as options.");

    }
    public function getSavedQuestionsFoldersByType(User $user, SavedQuestionsFolder $savedQuestionFolder): Response
    {

        return (in_array($user->role,[2,5]))
            ? Response::allow()
            : Response::deny("You are not allowed to retrieve folders.");

    }
    public function store(User $user, SavedQuestionsFolder $savedQuestionFolder): Response
    {

        return (in_array($user->role,[2,5]))
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

        return ($user->id === $savedQuestionFolder->user_id)
            ? Response::allow()
            : Response::deny("You are not allowed to delete that folder.");

    }

    /**
     * @param User $user
     * @param SavedQuestionsFolder $toFolder
     * @param SavedQuestionsFolder $fromFolder
     * @param Question $question
     * @return Response
     */
    public function move(User                 $user,
                         SavedQuestionsFolder $toFolder,
                         SavedQuestionsFolder $fromFolder,
                             Question             $question){
        $owns_folders=       DB::table('saved_questions_folders')
            ->where('user_id', $user->id)
            ->where('id', $toFolder->id)
            ->exists()
            && DB::table('saved_questions_folders')
                ->where('user_id', $user->id)
                ->where('id', $fromFolder->id)
                ->exists();
        $owns_question = false;
        switch($fromFolder->type){
            case('my_favorites'):
                $owns_question =DB::table('my_favorites')
                        ->where('user_id', $user->id)
                        ->where('question_id', $question->id)
                        ->where('folder_id',$fromFolder->id)
                        ->exists();
                break;
            case('my_questions'):
                $owns_question =DB::table('questions')
                    ->where('question_editor_user_id', $user->id)
                    ->where('id', $question->id)
                    ->where('folder_id',$fromFolder->id)
                    ->exists();
                break;

        }
        $authorized = $owns_folders && $owns_question;

        return $authorized
            ? Response::allow()
            : Response::deny("You are not allowed to move $question->title from $fromFolder->name to $toFolder->name.");

    }


}
