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

    public function learningTrees()  {
        $learningTrees = DB::table('assignment_question')
            ->join('assignment_question_learning_tree', 'assignment_question.id', '=', 'assignment_question_learning_tree.assignment_question_id')
            ->join('learning_trees', 'assignment_question_learning_tree.learning_tree_id', '=', 'learning_trees.id')
            ->where('assignment_id', $this->id)
            ->select('learning_tree', 'question_id')
            ->get();
        return collect($learningTrees);
    }


    public function submissions()
    {

        return $this->hasMany('App\Submission');

    }

}
