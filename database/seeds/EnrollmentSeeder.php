<?php

use Illuminate\Database\Seeder;
use App\User;
use App\Enrollment;

class EnrollmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = User::find(1);
        foreach (User::all()->where('id', '>',  $user->id) as $user) {
            Enrollment::create([
                'user_id' => $user->id,
                'course_id' => 1
            ]);
        }
    }
}
