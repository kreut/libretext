<?php

namespace App\Policies;

use App\Assignment;
use App\AutoRelease;
use App\Course;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class AutoReleasePolicy
{
    use HandlesAuthorization;

    public function getGlobalAutoReleaseUpdateOptions(User $user, AutoRelease $autoRelease, Course $course): Response
    {

        return $course->ownsCourseOrIsCoInstructor($user->id)
            ? Response::allow()
            : Response::deny('You are not allowed to get the global auto-release options for this course.');

    }

    public function compareAssignmentToCourseDefault(User $user): Response
    {

        return ($user->role === 2)
            ? Response::allow()
            : Response::deny('You are not allowed to compare the assignment auto-release to the default course auto-release.');

    }

    public function globalUpdate(User $user, AutoRelease $autoRelease, Course $course, int $update_item): Response
    {
        if ($update_item !== -1) {
            $course_id = Assignment::find($update_item)->course_id;
            $course = Course::find($course_id);
        }

        return $course->ownsCourseOrIsCoInstructor($user->id)
            ? Response::allow()
            : Response::deny('You are not allowed to globally update the auto-releases for this course.');

    }

    public function updateActivated(User $user, AutoRelease $autoRelease, Assignment $assignment): Response
    {

        return $assignment->course->ownsCourseOrIsCoInstructor($user->id)
            ? Response::allow()
            : Response::deny('You are not allowed to update the auto-release activation status.');

    }
}

