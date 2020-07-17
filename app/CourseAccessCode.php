<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Traits\AccessCodes;

class CourseAccessCode extends Model
{
    use AccessCodes;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['course_id', 'access_code'];


}
