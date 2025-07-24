<?php

namespace App\Policies;

use App\Course;
use App\LmsAPI;
use App\LtiRegistration;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class LmsAPIPolicy
{
    use HandlesAuthorization;

    public function getOauthUrl(User $user, LmsAPI $lmsAPI, Course $course)
    {
        return $course->ownsCourseOrIsCoInstructor($user->id)
            ? Response::allow()
            : Response::deny('You are not allowed to get the oAuth URL.');

    }
}
