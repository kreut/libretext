<?php

use Illuminate\Database\Seeder;
use App\Assignment;
use App\User;
use App\Grade;

class GradeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $assignment = Assignment::find(1);
        $users = User::where('id', '>', 5)->get();
        foreach ($users as $user){
            Grade::create([
                'user_id' => $user->id,
                'assignment_id' => $assignment->id,
                'grade' => 'C'
            ]);
        }
    }
}
