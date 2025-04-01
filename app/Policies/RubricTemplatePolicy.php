<?php

namespace App\Policies;

use App\Assignment;
use App\RubricPointsBreakdown;
use App\RubricTemplate;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class RubricTemplatePolicy
{
    use HandlesAuthorization;

    /**
     * @param User $user
     * @param RubricTemplate $rubricTemplate
     * @return Response
     */
    public function store(User           $user,
                          RubricTemplate $rubricTemplate): Response
    {
        return $user->role === 2
            ? Response::allow()
            : Response::deny("You are not allowed to create a rubric template.");

    }

    /**
     * @param User $user
     * @param RubricTemplate $rubricTemplate
     * @return Response
     */
    public function delete(User           $user,
                           RubricTemplate $rubricTemplate): Response
    {
        return $rubricTemplate->user_id === $user->id
            ? Response::allow()
            : Response::deny("You are not allowed to delete this rubric template.");

    }

    /**
     * @param User $user
     * @param RubricTemplate $rubricTemplate
     * @return Response
     */
    public function update(User           $user,
                           RubricTemplate $rubricTemplate): Response
    {
        return $rubricTemplate->user_id === $user->id
            ? Response::allow()
            : Response::deny("You are not allowed to update this rubric template.");

    }
    /**
     * @param User $user
     * @param RubricTemplate $rubricTemplate
     * @return Response
     */
    public function copy(User           $user,
                           RubricTemplate $rubricTemplate): Response
    {
        return $rubricTemplate->user_id === $user->id
            ? Response::allow()
            : Response::deny("You are not allowed to copy this rubric template.");

    }
}
