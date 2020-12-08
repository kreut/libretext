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

    public function seeds()
    {
        return $this->hasMany('App\Seed');
    }

    public function course()
    {
        return $this->belongsTo('App\Course');
    }

    public function fileSubmissions(){

        return $this->hasMany('App\SubmissionFile');
    }
    public function assignmentFileSubmissions()
    {
        return $this->hasMany('App\SubmissionFile')->where('type', 'a');
    }

    public function hasFileOrQuestionSubmissions() {
        dd($this->submissions->isNotEmpty() + $this->fileSubmissions->isNotEmpty());
       return  $this->submissions->isNotEmpty() + $this->fileSubmissions->isNotEmpty();
    }
    public function questionFileSubmissions()
    {
        $questionFileSubmissions = DB::table('submission_files')
            ->leftJoin('users','grader_id','=','users.id')
            ->where('type','q')
            ->where('assignment_id',$this->id)
            ->select('submission_files.*', DB::raw('CONCAT(users.first_name," ", users.last_name) AS grader_name'))
            ->get();
        return collect($questionFileSubmissions);
    }

    public function submissions()
    {

        return $this->hasMany('App\Submission');

    }

}
