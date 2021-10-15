<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class QuestionLevelOverride extends Model
{
    protected $guarded = [];
    public function hasOpenEndedOverride(int $assignment_id, int $question_id){
       return  DB::table('question_level_overrides')
           ->select('id')
            ->where('assignment_id', $assignment_id)
            ->where('user_id', Auth::user()->id)
            ->where('question_id', $question_id)
            ->where('open_ended', 1)
            ->first();
    }
    public function hasAutoGradedOverride(int $assignment_id, int $question_id){
        return  DB::table('question_level_overrides')
            ->select('id')
            ->where('assignment_id', $assignment_id)
            ->where('user_id', Auth::user()->id)
            ->where('question_id', $question_id)
            ->where('auto_graded', 1)
            ->first();
    }

}
