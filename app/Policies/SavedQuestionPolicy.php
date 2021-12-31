<?php

namespace App\Policies;

use App\Assignment;
use App\AssignmentSyncQuestion;
use App\Helpers\Helper;
use App\Question;
use App\SavedQuestion;
use App\SavedQuestionsFolder;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\DB;

class SavedQuestionPolicy
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
     * @param SavedQuestion $savedQuestion
     * @param Assignment $assignment
     * @return Response
     */
    public function getSavedQuestionsWithCourseLevelUsageInfo(User $user, SavedQuestion $savedQuestion, Assignment $assignment): Response
    {

        return (int)$assignment->course->user_id === (int)$user->id
            ? Response::allow()
            : Response::deny("You are not allowed to retrieve saved questions for that assignment.");

    }

    /**
     * @param User $user
     * @param SavedQuestion $savedQuestion
     * @param Assignment $assignment
     * @return Response
     */
    public function store(User $user, SavedQuestion $savedQuestion, Assignment $assignment): Response
    {

        return ($user->role === 2 && Helper::isCommonsCourse($assignment->course))
            ? Response::allow()
            : Response::deny("You are not allowed to save questions from this course.");

    }

    /**
     * @param User $user
     * @param SavedQuestion $savedQuestion
     * @param Assignment $assignment
     * @return Response
     */
    public function getSavedQuestionIdsByAssignment(User $user, SavedQuestion $savedQuestion, Assignment $assignment): Response
    {

        return ($user->role === 2 && Helper::isCommonsCourse($assignment->course))
            ? Response::allow()
            : Response::deny("You are not allowed to get the saved questions for this assignment.");

    }

    /**
     * @param User $user
     * @param SavedQuestion $savedQuestion
     * @param Question $question
     * @param SavedQuestionsFolder $fromFolder
     * @param SavedQuestionsFolder $toFolder
     * @return Response
     */
    public function move(User $user,
                         SavedQuestion $savedQuestion,
                         Question $question,
                         SavedQuestionsFolder $fromFolder,
                         SavedQuestionsFolder $toFolder){
        $authorized = $savedQuestion->where('user_id', $user->id)
                ->where('question_id', $question->id)
            ->where('folder_id',$fromFolder->id)
            ->exists()
            &&
            DB::table('saved_questions_folders')
            ->where('user_id', $user->id)
            ->where('id', $toFolder->id)
            ->exists();
        return $authorized
            ? Response::allow()
            : Response::deny("You are not allowed to move $question->title from $fromFolder->name to $toFolder->name.");

    }
    /**
     * @param User $user
     * @param SavedQuestion $savedQuestion
     * @param Question $question
     * @param SavedQuestionsFolder $savedQuestionsFolder
     * @return Response
     */
    public function destroy(User $user, SavedQuestion $savedQuestion, Question $question, SavedQuestionsFolder $savedQuestionsFolder): Response
    {
        $authorized = DB::table('saved_questions')
            ->where('question_id', $question->id)
            ->where('folder_id', $savedQuestionsFolder->id)
            ->where('user_id', $user->id)
            ->exists();
        return $authorized
            ? Response::allow()
            : Response::deny("You are not allowed to remove that saved question.");

    }


}
