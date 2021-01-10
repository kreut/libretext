<?php

namespace App\Policies;

use App\Course;
use App\User;
use App\Grader;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use \App\Traits\CommonPolicies;
use Illuminate\Support\Facades\DB;

class GraderPolicy
{
    use HandlesAuthorization;
    use CommonPolicies;


    public function store(User $user, Grader $Grader)
    {
        return (int) $user->role === 4
            ? Response::allow()
            : Response::deny('You are not allowed to add yourself to a course.');
    }


    public function getGraders(User $user, Grader $Grader, Course $course)
    {
        return $this->ownsCourseByUser($course, $user)
            ? Response::allow()
            : Response::deny('You are not allowed to get the graders.');
    }

    public function removeGraderFromCourse(User $user, Grader $Grader, Course $course, User $student_user)
    {
        $is_grader = DB::table('graders')
                    ->where('course_id', $course->id)
                    ->where('user_id', $student_user->id);
        return ($this->ownsCourseByUser($course, $user) && $is_grader)
            ? Response::allow()
            : Response::deny('You are not allowed to remove this grader.');
    }



}
