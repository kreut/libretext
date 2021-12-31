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
     * @param int $assignment_id
     * @param int $question_id
     * @param int $folder_id
     * @return Response
     */
    public function store(User $user, MyFavorite $myFavorite, int $assignment_id, int $question_id, int $folder_id): Response
    {

        if (!DB::table('saved_questions_folders')
            ->where('id', $folder_id)
            ->where('user_id', $user->id)
            ->where('type', 'my_favorites')
            ->first()) {
            return Response::deny("You are not allowed to save that the question to that folder.");
        }
        $is_commons_course = false;
        $is_public_course = false;
        $is_course_owner = false;
        if ($assignment_id) {
            $assignment = Assignment::find($assignment_id);
            $is_commons_course = Helper::isCommonsCourse($assignment->course);
            $is_public_course = $assignment->course->public;
            $is_course_owner = $assignment->course->user_id === $user->id;
        }

        $is_question_editor = Question::find($question_id)->question_editor_user_id === $user->id;
        return ($user->role === 2 && (
                $is_commons_course ||
                $is_course_owner ||
                $is_public_course ||
                $is_question_editor)
        )
            ? Response::allow()
            : Response::deny("You are not allowed to save that question to your Favorites.");

    }


    /**
     * @param User $user
     * @param MyFavorite $myFavorite
     * @param Assignment $assignment
     * @return Response
     */
    public function getMyFavoriteQuestionIdsByCommonsAssignment(User $user, MyFavorite $myFavorite, Assignment $assignment): Response
    {

        return ($user->role === 2 && Helper::isCommonsCourse($assignment->course))
            ? Response::allow()
            : Response::deny("You are not allowed to get the My Favorites questions for this assignment.");

    }
}
