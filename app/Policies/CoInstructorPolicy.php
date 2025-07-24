<?php

namespace App\Policies;

use App\CoInstructor;
use App\Course;
use App\Helpers\Helper;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class CoInstructorPolicy
{
    use HandlesAuthorization;
    /**
     * @param User $user
     * @param CoInstructor $coInstructor
     * @param Course $course
     * @return Response
     */
    public function destroy(User $user, CoInstructor $coInstructor, Course $course): Response
    {
        return $course->ownsCourseOrIsCoInstructor($user->id)
            ? Response::allow()
            : Response::deny('You are not allowed to remove a co-instructor from this course.');

    }

}
