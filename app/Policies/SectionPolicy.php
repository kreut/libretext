<?php

namespace App\Policies;

use App\Course;
use App\Section;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class SectionPolicy
{
    use HandlesAuthorization;


    public function destroy(User $user, Section $section)
    {

        return ((int)$section->course->user_id === $user->id)
            ? Response::allow()
            : Response::deny('You are not allowed to delete this section.');

    }

    /**
     * @param User $user
     * @param Section $section
     * @return Response
     */
    public function update(User $user, Section $section)
    {

        return ((int)$section->course->user_id === $user->id)
            ? Response::allow()
            : Response::deny('You are not allowed to update this section.');

    }

    /**
     * @param User $user
     * @param Section $section
     * @param Course $course
     * @return Response
     */
    public function store(User $user, Section $section, Course $course)
    {

        return ((int)$course->user_id === $user->id)
            ? Response::allow()
            : Response::deny('You are not allowed to create a section for this course.');

    }

    /**
     * @param User $user
     * @param Section $section
     * @return Response
     */
    public function refreshAccessCode(User $user, Section $section)
    {

        return ((int)$section->course->user_id === $user->id)
            ? Response::allow()
            : Response::deny('You are not allowed to refresh access codes for that section.');

    }
}
