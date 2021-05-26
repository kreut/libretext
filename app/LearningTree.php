<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LearningTree extends Model
{
    protected $guarded = [];

    /**
     * @return HasMany
     */
    public function learningTreeHistories(): HasMany
    {
       return $this->hasMany('App\LearningTreeHistory');
    }
}
