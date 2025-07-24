<?php

namespace App\Policies;

use App\Course;
use App\PendingCourseInvitation;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class PendingCourseInvitationPolicy
{
    use HandlesAuthorization;

    public function destroy(User                    $user,
                            PendingCourseInvitation $pendingCourseInvitation): Response
    {
        $course = Course::find($pendingCourseInvitation->course_id);
        return ($course->ownsCourseOrIsCoInstructor($user->id))
            ? Response::allow()
            : Response::deny('You are not allowed to delete this pending course invitation.');

    }

    /**
     * @param User $user
     * @param PendingCourseInvitation $pendingCourseInvitation
     * @param Course $course
     * @return Response
     */
    public function getPendingCourseInvitations(User                    $user,
                                                PendingCourseInvitation $pendingCourseInvitation,
                                                Course                  $course): Response
    {

        return ($course->ownsCourseOrIsCoInstructor($user->id))
            ? Response::allow()
            : Response::deny('You are not allowed to get the pending course invitations for this course.');

    }
}
