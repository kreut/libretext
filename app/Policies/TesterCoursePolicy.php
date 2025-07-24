<?php

namespace App\Policies;

use App\Course;
use App\TesterCourse;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\DB;

class TesterCoursePolicy
{
    use HandlesAuthorization;

    /**
     * @param User $user
     * @param TesterCourse $testerCourse
     * @param User $tester
     * @param Course $course
     * @return Response
     */
    public function destroy(User $user, TesterCourse $testerCourse, User $tester, Course $course): Response
    {

        $tester_exists_in_course = DB::table('tester_courses')
            ->where('user_id', $tester->id)
            ->where('course_id', $course->id)
            ->first();
        return $tester_exists_in_course && $course->ownsCourseOrIsCoInstructor($user->id)
            ? Response::allow()
            : Response::deny("You are not allowed to remove this tester.");
    }

}
