<?php

namespace App\Policies;

use App\Assignment;
use App\Helpers\Helper;
use App\MyFavorite;
use App\Question;
use App\SavedQuestionsFolder;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\DB;

class MyFavoritePolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * @param User $user
     * @param MyFavorite $myFavorite
     * @param Question $question
     * @param SavedQuestionsFolder $savedQuestionsFolder
     * @return Response
     */
    public function destroy(User $user, MyFavorite $myFavorite, Question $question, SavedQuestionsFolder $savedQuestionsFolder): Response
    {
        $authorized = DB::table('my_favorites')
            ->where('question_id', $question->id)
            ->where('folder_id', $savedQuestionsFolder->id)
            ->where('user_id', $user->id)
            ->exists();
        return $authorized
            ? Response::allow()
            : Response::deny("You are not allowed to remove that question from My Favorites.");

    }

    /**
     * @param User $user
     * @param MyFavorite $myFavorite
     * @param Assignment $assignment
     * @return Response
     */
    public function store(User $user, MyFavorite $myFavorite, int $question_id): Response
    {
return Response::allow();///have to think about this with questions not in courses
        return ($user->role === 2 && (Helper::isCommonsCourse($assignment->course)
            || $assignment->course->user_id === $user->id
            || $assignment->course->public
            || Question::find($question_id)->question_editor_user_id === $user->id))
            ? Response::allow()
            : Response::deny("You are not allowed to save questions to your Favorites.");

    }


    /**
     * @param User $user
     * @param MyFavorite $savedQuestion
     * @param Assignment $assignment
     * @return Response
     */
    public function getMyFavoriteQuestionIdsAssignment(User $user, MyFavorite $savedQuestion, Assignment $assignment): Response
    {

        return ($user->role === 2 && Helper::isCommonsCourse($assignment->course))
            ? Response::allow()
            : Response::deny("You are not allowed to get the saved questions for this assignment.");

    }
}
