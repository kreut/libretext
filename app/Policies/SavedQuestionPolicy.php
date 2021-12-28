<?php

namespace App\Policies;

use App\Assignment;
use App\AssignmentSyncQuestion;
use App\Helpers\Helper;
use App\SavedQuestion;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

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

        return (int) $assignment->course->user_id === (int) $user->id
            ? Response::allow()
            : Response::deny("You are not allowed to retrieve saved questions given that assignment id.");

    }

    /**
     * @param User $user
     * @param SavedQuestion $savedQuestion
     * @param Assignment $assignment
     * @return Response
     */
    public function store(User $user, SavedQuestion $savedQuestion, Assignment $assignment): Response
    {

        return ($user->role === 2 &&  Helper::isCommonsCourse($assignment->course))
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

        return ($user->role === 2 &&  Helper::isCommonsCourse($assignment->course))
            ? Response::allow()
            : Response::deny("You are not allowed to get the saved questions for this assignment.");

    }

    /**
     * @param User $user
     * @param SavedQuestion $savedQuestion
     * @return Response
     */
    public function destroy(User $user, SavedQuestion $savedQuestion): Response
    {

        return ($user->role === 2)
            ? Response::allow()
            : Response::deny("You are not allowed to remove saved questions.");

    }




}
