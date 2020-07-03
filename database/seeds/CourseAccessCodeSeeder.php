<?php

use Illuminate\Database\Seeder;
use App\Course;
use Faker\Factory;
use App\CourseAccessCode;

class CourseAccessCodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(CourseAccessCode $courseAccessCode)
    {
        $faker = Factory::create();
        foreach (Course::get() as $course) {
            $courseAccessCode->create([
                'course_id' => $course->id,
                'access_code' => $courseAccessCode->createCourseAccessCode()
            ]);
        }
    }
}
