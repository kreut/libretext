<?php

use Illuminate\Database\Seeder;
use App\User;
use App\Course;
use App\Course_Ta;

class CourseTaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = User::find(2);
        $course_1 = Course::find(1);
        $course_2 = Course::find(2);
        DB::table('course_ta')->insert([
            'user_id' => $user->id,
            'course_id' => $course_1->id
        ]);

        DB::table('course_ta')->insert([
            'user_id' => $user->id,
            'course_id' => $course_2->id
        ]);
    }
}
