<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class School extends Model
{
    /**
     * @param User $user
     * @return array
     */
    public function getLastSchool(User $user): array
    {
        $school_name = '';
        $school_id = 1;
        if ($user->role === 2) {
            $school = DB::table('courses')
                ->join('schools', 'courses.school_id', '=', 'schools.id')
                ->where('user_id', $user->id)
                ->orderBy('courses.created_at', 'desc')
                ->first();
            if ($school && ($school->school_id !== 1)) {
                $school_name = $school->name;
                $school_id = $school->school_id;
            }
        }
        return compact('school_name', 'school_id');
    }

    /**
     * @return HasMany
     */
    public function courses(): HasMany
    {
        return $this->hasMany('App\School');
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
