<?php

use Illuminate\Database\Seeder;
use App\Assignment;
use App\User;
use App\Grade;
use Faker\Factory;

class GradeSeeder extends Seeder
{
    /**
     * Randomly pick users to create C grades for all assignments
     *
     * @return void
     */
    public function run()
    {
        $faker = Factory::create();
        $assignments = Assignment::all();
        $num_users = count(User::all());
        foreach ($assignments as $assignment) {
            $randId = $faker->numberBetween(1, $num_users+1);
            $users = User::where('id', '>', $randId)->get();
            foreach ($users as $user) {
                Grade::create([
                    'user_id' => $user->id,
                    'assignment_id' => $assignment->id,
                    'grade' => 'C'
                ]);
            }
        }
    }
}
