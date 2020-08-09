<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LearningTree extends Model
{
    protected $fillable = ['question_id', 'user_id', 'learning_tree'];
}
