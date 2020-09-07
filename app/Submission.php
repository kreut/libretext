<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Submission extends Model
{
    protected $fillable = ['user_id', 'submission', 'assignment_id', 'question_id', 'score'];
}
