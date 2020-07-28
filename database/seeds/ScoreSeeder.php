<?php

use Illuminate\Database\Seeder;
use App\Assignment;
use App\User;
use App\Score;
use Faker\Factory;

class ScoreSeeder extends Seeder
{
    /**
     * Randomly pick users to create C scores for all assignments
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
                Score::create([
                    'user_id' => $user->id,
                    'assignment_id' => $assignment->id,
                    'score' => 'C'
                ]);
            }
        }
    }
}
