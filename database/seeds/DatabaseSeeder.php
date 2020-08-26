<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {

        $this->call([
            UserSeeder::class,
            CourseSeeder::class,
            CourseTaSeeder::class,
            CourseAccessCodeSeeder::class,
            EnrollmentSeeder::class,
            AssignmentSeeder::class,
            InstructorAccessCodeSeeder::class
        ]);
        //get the h5p questions
        echo "No questions seeded.";
        exit;
        Artisan::call('store:questions');
    }
}
