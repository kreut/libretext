<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MindTouchEvent extends Model
{
    protected $guarded = [];

    public function question(){
        return $this->hasOne('App\Question', 'page_id');
    }
}
