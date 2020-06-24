<?php

use Illuminate\Database\Seeder;

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
        for($i=0; $i<=10; $i++):
            DB::table('assignments')
                ->insert([
                    'name' => $faker->text(15),
                    'due_date' => $faker->dateTimeThisYear->format('Y-m-d H:i:s')
                ]);
        endfor;
    }
}
