<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LearningTree extends Model
{
    protected $guarded = ['user_id'];

    public function learningTreeHistories()
    {
       return $this->hasMany('App\LearningTreeHistory');
    }
}
