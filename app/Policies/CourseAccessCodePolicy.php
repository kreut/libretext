<?php

namespace App\Policies;

use App\Course;
use App\User;
use App\CourseAccessCode;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use \App\Traits\CommonPolicies;

class CourseAccessCodePolicy
{
    use HandlesAuthorization;
    use CommonPolicies;

    public function update(User $user, CourseAccessCode $CourseAccessCode, Course $course)
    {

        return $this->ownsCourseByUser($course, $user)
            ? Response::allow()
            : Response::deny('You are not allowed to refresh the course access codes.');
    }







}
