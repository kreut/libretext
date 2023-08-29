<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class School extends Model
{
    /**
     * @return HasMany
     */
    public function courses(): HasMany
    {
            return $this->hasMany('App\Http\Controllers\School');
    }

    /**
     * @return Model|Builder|object|null
     */
    public function LTIRegistration() {

      return DB::table('lti_schools')
            ->join('lti_registrations','lti_schools.lti_registration_id','=','lti_registrations.id')
            ->where('lti_schools.school_id', $this->id)
            ->first();

    }


}
