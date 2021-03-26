<?php

namespace App;


use Illuminate\Database\Eloquent\Model;

class AssignToPresetTiming extends Model
{
  public function assignToPresetGroups(){
      return $this->hasMany('App\AssignToPresetGroup');
  }
}
