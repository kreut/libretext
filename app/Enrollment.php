<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{

    protected $fillable = ['user_id', 'course_id'];

    public function enrolledUsers(){
        return $this->hasMany('App\User');
    }
}
