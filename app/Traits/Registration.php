<?php


namespace App\Traits;


use App\Grader;
use App\GraderAccessCode;
use Exception;
use Illuminate\Support\Facades\DB;

trait Registration
{
    public function addGraderToCourse(int $user_id, array $section_ids)
    {
        $grader = DB::table('graders')
            ->where('user_id', $user_id)
            ->whereIn('section_id', $section_ids)
            ->get()
            ->isNotEmpty();
        foreach ($section_ids as $section_id) {
            Grader::firstOrCreate(['user_id' => $user_id, 'section_id' => $section_id]);
        }
    }

    /**
     * @param $data
     * @return array
     * @throws Exception
     */
    public function setRole($data): array
    {
        $section_ids = [];
        $role = false;
        switch ($data['registration_type']) {
            case('instructor'):
                DB::table('instructor_access_codes')->where('access_code', $data['access_code'])->delete();
                $role = 2;
                break;
            case('student'):
                $role = 3;
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
            case('question editor'):
                $role = 5;
                break;
            case('tester'):
                DB::table('tester_access_codes')->where('access_code', $data['access_code'])->delete();
                $role = 6;
                break;

        }

        return [$section_ids, $role];
    }
}
