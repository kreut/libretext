<?php


namespace App\Traits;


use App\GraderAccessCode;
use Illuminate\Support\Facades\DB;

trait Registration
{
    public function addGraderToCourse($user_id, $course_id){
            $grader= DB::table('graders')
                ->where('user_id', $user_id)
                ->where('course_id', $course_id)
                ->get()
                ->isNotEmpty();
            if (!$grader) {
                DB::table('graders')->insert(
                    ['user_id' => $user_id,
                        'course_id' => $course_id,
                        'created_at' => now(),
                        'updated_at' => now()]
                );
            }
    }
   public function setRole($data){
       $course_id = 0;
       $role = false;
       switch ($data['registration_type']) {
           case('student'):
               $role = 3;
               break;
           case('instructor'):
               DB::table('instructor_access_codes')->where('access_code', $data['access_code'])->delete();
               $role = 2;
               break;
           case('grader'):
               $course_id = DB::table('grader_access_codes')->where('access_code', $data['access_code'])->value('course_id');
               GraderAccessCode::where('access_code', $data['access_code'])->delete();
               $role = 4;
               break;

       }
       return [$course_id,$role];
   }
}
