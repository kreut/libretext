<?php

namespace App\Policies;

use App\AlphaCourseImportCode;
use App\Course;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;


class AlphaCourseImportCodePolicy
{
    use HandlesAuthorization;

    public function show(User $user, AlphaCourseImportCode $alphaCourseImportCode, Course $course){

        return $course->user_id === $user->id
            ? Response::allow()
            : Response::deny('You are not allowed to access the Alpha Course Import code for this course.');
    }

    public function refresh(User $user, AlphaCourseImportCode $alphaCourseImportCode, Course $course){

        return $course->user_id === $user->id
            ? Response::allow()
            : Response::deny('You are not allowed to refresh the Alpha Course Import code for this course.');
    }
}
