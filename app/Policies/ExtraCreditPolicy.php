<?php

namespace App\Policies;

use App\Course;
use App\ExtraCredit;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class ExtraCreditPolicy
{
    use HandlesAuthorization;

    public function canAccessExtraCredit(Course $course, User $user,int  $student_user_id){
        $owner_of_course = (int) $course->user_id === (int) $user->id;
        $student_in_course = $course->enrollments->contains('user_id', $student_user_id);
        return  $owner_of_course && $student_in_course;
    }

    public function store(User $user, ExtraCredit $extraCredit, Course $course, int $student_user_id)
    {

        return $this->canAccessExtraCredit($course, $user, $student_user_id)
            ? Response::allow()
            : Response::deny('You are not allowed to give this student extra credit.');
    }

    public function show(User $user, ExtraCredit $extraCredit, Course $course, int $student_user_id)
    {

        return $this->canAccessExtraCredit($course, $user, $student_user_id)
            ? Response::allow()
            : Response::deny("You are not allowed to view this student's extra credit.");
    }



}
