<?php

use Illuminate\Database\Seeder;
use App\User;
use Faker\Factory;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::create([
            'first_name' => 'Eric',
            'last_name' => 'Kean',
            'email' => 'me@me.com',
            'password' => '$2y$10$yGJn0yActFr2GKvCDnMSMu/ICqG.wfveJgjG1iM.1mjZjteAMUd/G',
            'time_zone' => 'America/Los_Angeles',
            'role' => 2
        ]);
        $faker = Factory::create();

        for($i=0; $i<=9; $i++):
            User::create([
                'first_name' => $faker->firstName,
                'last_name' => $faker->lastName,
                'role' => 2,
                'email' => $faker->email,
                'password' => $faker->password
            ]);
        endfor;

        User::create(['first_name' => 'Fake',
            'last_name' => 'Student',
            'role' => 3]);

        User::create([
            'first_name' => 'Ima',
            'last_name' => 'Student',
            'email' => 'some@student.com',
            'password' => '$2y$10$yGJn0yActFr2GKvCDnMSMu/ICqG.wfveJgjG1iM.1mjZjteAMUd/G',
            'role' => 2
        ]);

    }
}
