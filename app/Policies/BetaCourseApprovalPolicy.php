<?php

namespace App\Policies;

use App\BetaCourseApproval;
use App\Course;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class BetaCourseApprovalPolicy
{
    use HandlesAuthorization;

    public function getByCourse(User $user, BetaCourseApproval $betaCourseApproval, Course $course){

        return $course->ownsCourseOrIsCoInstructor($user->id)
            ? Response::allow()
            : Response::deny('You are not allowed to retrieve the pending approvals.');


    }
}
