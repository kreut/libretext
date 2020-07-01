<?php

use Illuminate\Database\Seeder;
use Carbon\Carbon;
use App\User;
use App\Course;

class CourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = \Faker\Factory::create();
        $user = User::find(1);
        $start_date = Carbon::now();
        for($i=0; $i<=2; $i++):
            Course::create([
                'name' => $faker->text(15),
                'user_id' => $user->id,
                'start_date' => $start_date->format('Y-m-d'),
                'end_date' => $start_date->add('2 years')->format('Y-m-d')
            ]);

        endfor;
    }
}
