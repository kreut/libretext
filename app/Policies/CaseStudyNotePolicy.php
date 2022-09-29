<?php

namespace App\Policies;

use App\Assignment;
use App\CaseStudyNote;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use App\Traits\CommonPolicies;


class CaseStudyNotePolicy
{
    use HandlesAuthorization;
    use CommonPolicies;

    /**
     * @param User $user
     * @param CaseStudyNote $caseStudyNote
     * @param Assignment $assignment
     * @return Response
     */
    public function show(User $user, CaseStudyNote $caseStudyNote, Assignment $assignment): Response
    {
        return $user->id === $assignment->course->user_id
            ? Response::allow()
            : Response::deny('You are not allowed to retrieve these Case Study Notes.');

    }

}
