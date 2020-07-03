<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CourseAccessCode extends Model
{
    public function createCourseAccessCode() {
        return substr(sha1(mt_rand()), 17, 8);
    }
}
