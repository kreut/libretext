<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{

    public function enrolledUsers(){
        return $this->hasMany('App\User');
    }
}
