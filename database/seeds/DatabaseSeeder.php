<?php

use Illuminate\Database\Seeder;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $faker = \Faker\Factory::create();
        for($i=0; $i<=2; $i++):
            $start_date = Carbon::now();
            DB::table('courses')
                ->insert([
                    'name' => $faker->text(15),
                    'user_id' => 1,
                    'start_date' => $start_date->format('Y-m-d H:i:s'),
                    'end_date' => $start_date->add('2 years')->format('Y-m-d H:i:s')
                ]);
        endfor;
        $current_date = $start_date;
        for($i=0; $i<=10; $i++):
            $current_date = $current_date->add('1 week');
            DB::table('assignments')
                ->insert([
                    'name' => $faker->text(15),
                    'available_on' => $current_date->add(($i+2) . ' weeks')->format('Y-m-d H:i:s'),
                    'due_date' => $current_date->add(($i+3) . ' weeks')->format('Y-m-d H:i:s'),
                    'course_id' => 1
                ]);
        endfor;
    }
}
