<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LearningTreeHistory extends Model
{
    public function learningTree()
    {
        return $this->belongsTo('App\LearningTree');
    }
}
