<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    protected $guarded = [];

    public function questions()
    {
        return $this->belongsToMany('App\Question')->withTimestamps();
    }

    public function scores()
    {
        return $this->hasMany('App\Score');
    }

    public function course() {
        return $this->belongsTo('App\Course');
    }
}
