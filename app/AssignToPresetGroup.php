<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AssignToPresetGroup extends Model
{

    public function assignToPresetTimings(){
        return $this->belongsTo('App\AssignToPresetTiming');
    }
}
