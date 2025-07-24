<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CourseOrder extends Model
{
    /**
     * @param User $user
     * @param array $ordered_courses
     */
    public
    function orderCourses(User $user, array $ordered_courses)
    {
        foreach ($ordered_courses as $key => $course_id) {
            DB::table('course_orders')
                ->where('course_id', $course_id)
                ->where('user_id', $user->id)
                ->update(['order' => $key + 1]);
        }
    }

    /**
     * @param User $user
     */
    public
    function reOrderAllCourses(User $user)
    {
        $course = new Course();
        $courses = $course->getCourses($user);
        $all_course_ids = [];
        if ($courses) {
            foreach ($courses as $value) {
                $all_course_ids[] = $value->id;
            }
            $this->orderCourses($user, $all_course_ids);
        }
    }
}
