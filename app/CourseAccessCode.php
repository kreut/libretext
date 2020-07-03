<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CourseAccessCode extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['course_id', 'access_code'];



    public function createCourseAccessCode() {
        return substr(sha1(mt_rand()), 17, 8);
    }

}
