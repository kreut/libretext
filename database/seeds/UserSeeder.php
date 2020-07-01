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
            'name' => 'Eric Kean',
            'email' => 'me@me.com',
            'password' => '$2y$10$yGJn0yActFr2GKvCDnMSMu/ICqG.wfveJgjG1iM.1mjZjteAMUd/G'
        ]);

        $faker = Factory::create();

        for($i=0; $i<=10; $i++):
            User::create([
                'name' => $faker->name,
                'email' => $faker->email,
                'password' => $faker->password
            ]);

        endfor;

    }
}
