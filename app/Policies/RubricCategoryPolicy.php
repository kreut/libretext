<?php

namespace App\Policies;

use App\Assignment;
use App\RubricCategory;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use App\Traits\CommonPolicies;

class RubricCategoryPolicy
{
    use HandlesAuthorization;
    use CommonPolicies;

    public
    function store(User $user, RubricCategory $rubric, Assignment $assignment): Response
    {
        return $this->isOwnerOrGrader($assignment, $user)
            ? Response::allow()
            : Response::deny('You are not allowed to save a rubric category for this assignment.');

    }

    public
    function order(User $user, RubricCategory $rubric, Assignment $assignment): Response
    {
        return $this->isOwnerOrGrader($assignment, $user)
            ? Response::allow()
            : Response::deny('You are not allowed to re-order the rubric categories for this assignment.');

    }

    public
    function update(User $user, RubricCategory $rubric): Response
    {
        $assignment = Assignment::find($rubric->assignment_id);
        return $this->isOwnerOrGrader($assignment, $user)
            ? Response::allow()
            : Response::deny('You are not allowed to update this rubric category.');

    }

    public
    function destroy(User $user, RubricCategory $rubric): Response
    {
        $assignment = Assignment::find($rubric->assignment_id);
        return $this->isOwnerOrGrader($assignment, $user)
            ? Response::allow()
            : Response::deny('You are not allowed to delete this rubric category.');

    }

}
