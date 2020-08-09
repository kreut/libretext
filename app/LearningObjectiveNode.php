<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LearningObjectiveNode extends Model
{
    protected $fillable = ['user_id', 'learning_objective_id', 'library', 'page_id'];
    protected $table = 'learning_objective_node';
}
