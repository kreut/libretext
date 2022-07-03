<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LearningOutcomeNode extends Model
{
    protected $fillable = ['user_id', 'learning_outcome_id', 'library', 'page_id'];
    protected $table = 'learning_outcome_node';
}
