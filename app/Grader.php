<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Grader extends Model
{
    protected $guarded = [];

    public function enrollmentsByCourse(int $course_id){
       $enrolled_user_ids = DB::table('graders')
           ->join('enrollments','graders.section_id','=','enrollments.section_id')
           ->join('sections','graders.section_id','=','sections.id')
            ->join('users','enrollments.user_id','=','users.id')
            ->select('users.id')
            ->where('sections.course_id',$course_id)
           ->where('users.fake_student',0)
           ->where('graders.user_id', Auth::user()->id)
            ->get()
           ->pluck('id')
       ->toArray();
       return User::whereIn('id', $enrolled_user_ids)->get();
    }
}
