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
            'password' => '$2y$10$yGJn0yActFr2GKvCDnMSMu/ICqG.wfveJgjG1iM.1mjZjteAMUd/G'
        ]);

        $faker = Factory::create();
        User::create(['first_name' => 'Fake',
        'last_name' => 'Student']);

        for($i=0; $i<=9; $i++):
            User::create([
                'first_name' => $faker->firstName,
                'last_name' => $faker->lastName,
                'email' => $faker->email,
                'password' => $faker->password
            ]);

        endfor;

    }
}
