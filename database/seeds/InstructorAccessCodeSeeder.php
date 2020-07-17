<?php

use Illuminate\Database\Seeder;

class InstructorAccessCodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(\App\InstructorAccessCode $instructorAccessCode)
    {
        for ($i = 0; $i <= 9; $i++):
            $instructorAccessCode->create([
                'access_code' => $instructorAccessCode->createCourseAccessCode()
            ]);
        endfor;
    }
}
