<?php

namespace App\Policies;

use App\CoInstructor;
use App\Course;
use App\CourseOrder;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\DB;

class CourseOrderPolicy
{
    use HandlesAuthorization;


    /**
     * @param User $user
     * @param CourseOrder $courseOrder
     * @param $ordered_courses
     * @return Response
     */
    public function order(User $user, CourseOrder $courseOrder, $ordered_courses): Response
    {
        $owner_courses = Course::where('user_id', $user->id)
            ->select('id')
            ->pluck('id')
            ->toArray();
        $co_instructor_courses = CoInstructor::where('user_id', $user->id)
            ->where('status', 'accepted')
            ->select('course_id')
            ->pluck('course_id')
            ->toArray();
        $has_access = true;
        $courses = array_merge($owner_courses, $co_instructor_courses);
        foreach ($ordered_courses as $ordered_course) {
            if (!in_array($ordered_course, $courses)) {
                $has_access = false;
            }
        }
        return $has_access
            ? Response::allow()
            : Response::deny('You are not allowed to re-order a course that is not yours.');
    }
}
