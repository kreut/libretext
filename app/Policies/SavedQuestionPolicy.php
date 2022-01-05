<?php

namespace App\Policies;

use App\Assignment;
use App\AssignmentSyncQuestion;
use App\Helpers\Helper;
use App\Question;
use App\MyFavorite;
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
     * @param MyFavorite $savedQuestion
     * @param Assignment $assignment
     * @return Response
     */
    public function getSavedQuestionsWithCourseLevelUsageInfo(User $user, MyFavorite $savedQuestion, Assignment $assignment): Response
    {

        return (int)$assignment->course->user_id === (int)$user->id
            ? Response::allow()
            : Response::deny("You are not allowed to retrieve saved questions for that assignment.");

    }



    /**
     * @param User $user
     * @param MyFavorite $savedQuestion
     * @param Assignment $assignment
     * @return Response
     */
    public function getSavedQuestionIdsByAssignment(User $user, MyFavorite $savedQuestion, Assignment $assignment): Response
    {

        return ($user->role === 2 && Helper::isCommonsCourse($assignment->course))
            ? Response::allow()
            : Response::deny("You are not allowed to get the saved questions for this assignment.");

    }




}
