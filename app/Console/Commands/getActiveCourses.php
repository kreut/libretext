<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class getActiveCourses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:activeCourses';

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
            $active_course_ids = DB::table('enrollments')
                ->where('created_at', '>', $date)
                ->select('course_id')
                ->groupBy('course_id')
                ->havingRaw('COUNT(*) >= 2')
                ->pluck('course_id');
            $instructors_with_something_due_saturday = DB::table('assign_to_timings')
                ->join('assignments', 'assign_to_timings.assignment_id', '=', 'assignments.id')
                ->join('courses', 'assignments.course_id', '=', 'courses.id')
                ->whereIn('course_id', $active_course_ids)
                ->where(function ($query) {
                    return $query
                        ->where('due', 'LIKE', '2025-03-15%')
                        ->orWhere('final_submission_deadline', 'LIKE', '2025-03-15%');
                })
                ->select('courses.user_id AS user_id')
                ->get()
                ->pluck('user_id')
                ->toArray();
            $instructors = DB::table('courses')
                ->join('users', 'courses.user_id', '=', 'users.id')
                ->select('users.id', 'users.first_name', 'users.last_name', 'users.email')
                ->whereIn('courses.id', $active_course_ids)
                ->distinct()
                ->get();
            $fileName = '/Users/franciscaparedes/Downloads/instructors.csv';


// Add CSV headers


// Add instructor data
            foreach ($instructors as $instructor) {
                $instructor->assignment_due_saturday = in_array($instructor->id, $instructors_with_something_due_saturday) ? 'Y' : 'N';
            }
// Open a file in write mode
            $file = fopen("$fileName", 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
// Add CSV headers
            fputcsv($file, ['ID', 'First Name', 'Last Name', 'Email','Assignment Due Saturday']);

// Write instructor data to CSV
            foreach ($instructors as $instructor) {
                fputcsv($file, [(int)$instructor->id,
                    $instructor->first_name,
                    $instructor->last_name,
                    $instructor->email,
                    $instructor->assignment_due_saturday]);
            }

// Close the file
            fclose($file);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
        return 0;
    }
}
