<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Traits\AccessCodes;
use Illuminate\Support\Facades\DB;


class CourseAccessCode extends Model
{
    use AccessCodes;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['course_id', 'access_code'];

    public function refreshCourseAccessCode($course_id) {
        $access_code = $this->createCourseAccessCode();
        $courseAccessCode = new CourseAccessCode();
        $courseAccessCode->updateOrCreate(
            ['course_id' => $course_id],
            ['access_code' => $access_code]
        );
        return $access_code;
    }

}
