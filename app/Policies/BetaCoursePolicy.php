<?php

namespace App\Policies;

use App\BetaCourse;
use App\Course;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use \App\Traits\CommonPolicies;

class BetaCoursePolicy
{
    use HandlesAuthorization;
    use CommonPolicies;


    public function untetherBetaCourseFromAlphaCourse(User $user, BetaCourse $betaCourse, Course $course){
        return $course->ownsCourseOrIsCoInstructor($user->id)
            ? Response::allow()
            : Response::deny('You are not allowed to untether this Beta Course.');


    }

    public function getTetheredToAlphaCourse(User $user, BetaCourse $betaCourse, Course $course){

        return $course->ownsCourseOrIsCoInstructor($user->id)
            ? Response::allow()
            : Response::deny('You are not allowed to retrieve the tethered to Alpha Course.');


    }

    public function getBetaCoursesFromAlphaCourse(User $user, BetaCourse $betaCourse, Course $alpha_course){

        return $alpha_course->user_id === $user->id
            ? Response::allow()
            : Response::deny('You are not allowed to retrieve those Beta courses.');


    }

}
