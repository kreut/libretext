<?php


namespace App\Traits;


use App\Grader;
use App\GraderAccessCode;
use Illuminate\Support\Facades\DB;

trait Registration
{
    public function addGraderToCourse(int $user_id, array $section_ids){
            $grader= DB::table('graders')
                ->where('user_id', $user_id)
                ->whereIn('section_id', $section_ids)
                ->get()
                ->isNotEmpty();
            foreach ($section_ids as $section_id){
                Grader::firstOrCreate(['user_id' => $user_id, 'section_id'=>$section_id]);
            }
    }
   public function setRole($data){
       $section_ids = [];
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
               $section_ids = DB::table('grader_access_codes')
                    ->where('access_code', $data['access_code'])
                    ->get()
                   ->pluck('section_id')
                   ->toArray();
               GraderAccessCode::where('access_code', $data['access_code'])->delete();
               $role = 4;
               break;

       }

       return [$section_ids,$role];
   }
}
