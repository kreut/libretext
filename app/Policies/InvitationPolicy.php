<?php

namespace App\Policies;

use App\User;
use App\Course;
use App\Invitation;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class InvitationPolicy
{
    use HandlesAuthorization;

    public function emailCoInstructorInvitation(User $user, Invitation $invitation, Course $course): Response
    {

        return ($user->email !=='commons@libretexts.org') && $course->ownsCourseOrIsCoInstructor($user->id)
            ? Response::allow()
            : Response::deny('You are not allowed to invite co-instructors to this course.');

    }
    public function emailGraderInvitation(User $user, Invitation $invitation, Course $course): Response
    {

        return ($user->email !=='commons@libretexts.org') && $course->ownsCourseOrIsCoInstructor($user->id)
            ? Response::allow()
            : Response::deny('You are not allowed to invite graders to this course.');

    }
}
