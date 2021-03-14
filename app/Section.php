<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Section extends Model
{
    public function course()
    {
        return $this->belongsTo('App\Course');
    }

    public function enrolledUsers()
    {
        return $this->hasManyThrough('App\User',
            'App\Enrollment',
            'section_id', //foreign key on enrollments table
            'id', //foreign key on users table
            'id', //local key in courses table
            'user_id');

    }

    public function isGrader()
    {
        $graders = DB::table('graders')
            ->join('sections', 'graders.section_id', '=', 'sections.id')
            ->where('sections.id', $this->id)
            ->select('user_id')
            ->get()
            ->pluck('user_id')
            ->toArray();
        return (in_array(Auth::user()->id, $graders));
    }

    public function graders()
    {
        return $this->hasMany('App\Grader');
    }
}
