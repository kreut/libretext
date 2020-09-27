<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
    public function assignmentFileSubmissions()
    {
        return $this->hasMany('App\SubmissionFile')->where('type','a');
    }

    public function questionFileSubmissions()
    {
        return $this->hasMany('App\SubmissionFile')->where('type','q');
    }

    public function submissions(){

      return $this->hasMany('App\Submission');

    }

}
