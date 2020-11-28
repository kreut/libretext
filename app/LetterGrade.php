<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LetterGrade extends Model
{
    protected $guarded = [];

    public function defaultLetterGrades(){
        return '90,A,80,B,70,C,60,D,0,F';
    }
}
