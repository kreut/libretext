<?php

use Illuminate\Database\Seeder;
use Carbon\Carbon;
use App\User;
use App\Course;
use Faker\Factory;

class CourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Factory::create();
        $user = User::find(1);
        $start_date = Carbon::now();
            Course::create([
                'name' => 'First Course',
                'user_id' => $user->id,
                'start_date' => $start_date->format('Y-m-d'),
                'end_date' => $start_date->add('2 years')->format('Y-m-d')
            ]);

    }
}
