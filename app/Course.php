<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'start_date', 'end_date', 'user_id'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function grades() {
        return $this->hasManyThrough('App\Grade', 'App\Assignment');
    }

    public function enrolledUsers() {
        return $this->hasManyThrough('App\User',
            'App\Enrollment',
            'course_id', //foreign key on enrollments table
            'id', //foreign key on users table
            'id', //local key in courses table
            'user_id'); //local key in enrollments table
    }
    public function assignments() {
        return $this->hasMany('App\Assignment')->orderBy('due_date', 'asc');
    }


}
