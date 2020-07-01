<?php

use Illuminate\Database\Seeder;
use Carbon\Carbon;
use App\User;
use App\Course;
use App\Enrollment;
use App\Assignment;
use App\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {

        //users
        $my_user_id = 1;
        User::create([
                'name' => 'Eric Kean',
                'email' => 'me@me.com',
                'password' => '$2y$10$yGJn0yActFr2GKvCDnMSMu/ICqG.wfveJgjG1iM.1mjZjteAMUd/G'
            ]);

        Role::create([
            'user_id' => $my_user_id,
            'name' => 'instructor'
        ]);

        $faker = \Faker\Factory::create();
        for($i=0; $i<=10; $i++):
            User::create([
                    'name' => $faker->name,
                    'email' => $faker->email,
                    'password' => $faker->password
                ]);

            endfor;

        //courses and enrollment

        for($i=0; $i<=2; $i++):
            $start_date = Carbon::now();
            Course::create([
                    'name' => $faker->text(15),
                    'user_id' => $my_user_id,
                    'start_date' => $start_date->format('Y-m-d'),
                    'end_date' => $start_date->add('2 years')->format('Y-m-d')
                ]);

        endfor;

        //enrollment
        foreach (User::all()->where('id', '>',  $my_user_id) as $user) {
            Enrollment::create([
                    'user_id' => $user->id,
                    'course_id' => 1
                ]);
            Role::create([
                'user_id' => $user->id,
                'name' => 'student'
            ]);
        }

        //assignments
        $current_date = $start_date;
        for($i=0; $i<=10; $i++):
            $current_date = $current_date->add('1 week');
            Assignment::create([
                    'name' => $faker->text(15),
                    'available_on' => $current_date->add(($i+2) . ' weeks')->format('Y-m-d H:i:s'),
                    'due_date' => $current_date->add(($i+3) . ' weeks')->format('Y-m-d H:i:s'),
                    'course_id' => 1
                ]);
        endfor;
    }
}
