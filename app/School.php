<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class School extends Model
{
    public function courses(){
        return $this->hasMany('App\School');
    }
}
