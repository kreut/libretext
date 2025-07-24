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
    public function getUnsavedChanges(User $user, CaseStudyNote $caseStudyNote, Assignment $assignment): Response
    {

        return $assignment->course->ownsCourseOrIsCoInstructor($user->id)
            ? Response::allow()
            : Response::deny('You are not allowed to get the unsaved changes.');
    }

    public function destroy(User $user, CaseStudyNote $caseStudyNote): Response
    {

        return $user->id === Assignment::find($caseStudyNote->assignment_id)->course->user_id
            ? Response::allow()
            : Response::deny('You are not allowed to delete these Case Study Notes.');

    }


    public function resetAssignmentNotes(User $user, CaseStudyNote $caseStudyNote, Assignment $assignment): Response
    {
        return $assignment->course->ownsCourseOrIsCoInstructor($user->id)
            ? Response::allow()
            : Response::deny('You are not allowed to reset these Case Study Notes.');

    }

    public function update(User $user, CaseStudyNote $caseStudyNote, Assignment $assignment): Response
    {
        return $assignment->course->ownsCourseOrIsCoInstructor($user->id)
            ? Response::allow()
            : Response::deny('You are not allowed to update these Case Study Notes.');

    }


    /**
     * @param User $user
     * @param CaseStudyNote $caseStudyNote
     * @param Assignment $assignment
     * @return Response
     */
    public function show(User $user, CaseStudyNote $caseStudyNote, Assignment $assignment): Response
    {
        return $assignment->course->ownsCourseOrIsCoInstructor($user->id)
            ? Response::allow()
            : Response::deny('You are not allowed to retrieve these Case Study Notes.');

    }

    /**
     * @param User $user
     * @param CaseStudyNote $caseStudyNote
     * @param Assignment $assignment
     * @return Response
     */
    public function saveAll(User $user, CaseStudyNote $caseStudyNote, Assignment $assignment): Response
    {
        return $assignment->course->ownsCourseOrIsCoInstructor($user->id)
            ? Response::allow()
            : Response::deny('You are not allowed to save all of the Case Study Notes.');

    }

}
