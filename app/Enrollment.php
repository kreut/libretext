<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{

    protected $guarded = [];

    public function enrolledUsers()
    {
        return $this->hasMany('App\User');
    }

    /**
     * @param int $role
     * @param Course $course
     * @param int $section_id
     * @return mixed
     */
    public function getEnrolledUsersByRoleCourseSection(int $role, Course $course, int $section_id)
    {
        $enrolled_users = collect([]);
        switch ($section_id === 0) {
            case(true):
                $grader = new Grader();
                $enrolled_users = in_array($role, [2,3])
                    ? $course->enrolledUsers
                    : $grader->enrollmentsByCourse($course->id);

                break;
            case(false):
                $section = new Section();
                $enrolled_users = $section->find($section_id)->enrolledUsers;
                break;
        }
        return $enrolled_users;
    }
}
