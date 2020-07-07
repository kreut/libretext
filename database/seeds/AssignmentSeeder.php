<?php

use Illuminate\Database\Seeder;
use App\Assignment;
use App\Course;
use Carbon\Carbon;

class AssignmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = \Faker\Factory::create();
        $course = Course::find(1);
        $current_date = Carbon::now();

        for ($i = 0; $i <= 10; $i++):
            $current_date = $current_date->add('1 week');
            Assignment::create([
                'name' => $faker->text(15),
                'available_on' => $current_date->add(($i + 2) . ' weeks')->format('Y-m-d H:i:00'),
                'due_date' => $current_date->add(($i + 3) . ' weeks')->format('Y-m-d H:i:00'),
                'num_submissions_needed' => $faker->randomElement([2, 3, 4, 5, 6, 7, 8, 9, 10]),
                'type_of_submission' => $faker->randomElement(['completed', 'correct']),
                'course_id' => $course->id
            ]);
        endfor;
    }
}
