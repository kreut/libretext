<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class getEnrolledUsersInActiveCourses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:enrolledUsersInActiveCourses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            $date = "2025-01-01 00:00:00";


            $coursesWithEnrollment = DB::table('enrollments')
                ->join('courses', 'enrollments.course_id', '=', 'courses.id') // Join courses
                ->join('schools', 'courses.school_id', '=', 'schools.id') // Join schools
                ->where('enrollments.created_at', '>', $date)
                ->select(
                    'enrollments.course_id',
                    'schools.name as school_name',
                    DB::raw('COUNT(enrollments.id) as enrollment_count')
                )
                ->groupBy('enrollments.course_id', 'schools.name')
                ->havingRaw('COUNT(enrollments.id) >= 2')
                ->get();

// Define the absolute file path to save the CSV file
            $csvFilePath = '/Users/franciscaparedes/Downloads/active_courses.csv';

// Open the file for writing
            $file = fopen($csvFilePath, 'w');

// Write the column headers to the CSV file
            fputcsv($file, ['course_id', 'school_name', 'enrollment_count']);

// Write each row of data to the CSV file
            foreach ($coursesWithEnrollment as $course) {
                fputcsv($file, [
                    $course->course_id,
                    $course->school_name,
                    $course->enrollment_count,
                ]);
            }

// Close the file after writing
            fclose($file);
            echo "done";
            return 0;


        } catch (Exception $e) {
            echo $e->getMessage();
        }
        return 1;
    }
}
