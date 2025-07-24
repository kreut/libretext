<?php

namespace App\Policies;

use App\Assignment;
use App\DiscussionGroup;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class DiscussionGroupPolicy
{
    use HandlesAuthorization;

    /**
     * @param User $user
     * @param DiscussionGroup $discussionGroup
     * @param Assignment $assignment
     * @return Response
     */
    public function getByAssignmentQuestionUser(User            $user,
                                                DiscussionGroup $discussionGroup,
                                                Assignment      $assignment): Response
    {

        $course = Assignment::find($assignment->id)->course;
        $enrolled = $course->enrollments->contains('user_id', $user->id);
        $has_access = $user->role === 3
            ? $enrolled
            : $course->ownsCourseOrIsCoInstructor($user->id);


        return $has_access
            ? Response::allow()
            : Response::deny('You are not allowed to get the discussion group information.');

    }
}
