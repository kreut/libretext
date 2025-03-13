<?php

namespace App\Console\Commands;

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
                ->distinct()
                ->get()
                ->pluck('course_id');
            $instructors = DB::table('courses')
                ->join('users', 'courses.user_id', '=', 'users.id')
                ->select('users.id','users.first_name', 'users.last_name','users.email')
                ->whereIn('courses.id', $active_course_ids)
                ->distinct()
                ->get()
               ;
            $fileName = '/Users/franciscaparedes/Downloads/instructors.csv';
            $data = [];

// Add CSV headers
            $data[] = ['ID', 'First Name', 'Last Name', 'Email'];

// Add instructor data
            foreach ($instructors as $instructor) {
                $data[] = [
                    (int) $instructor->id,
                    $instructor->first_name,
                    $instructor->last_name,
                    $instructor->email
                ];
            }

// Convert data to CSV format
            $csvContent = '';
            foreach ($data as $row) {
                $csvContent .= implode(',', array_map(fn($value) => '"'.str_replace('"', '""', $value).'"', $row)) . "\n";
            }

// Convert to UTF-8 with BOM
            $csvContent = "\xEF\xBB\xBF" . mb_convert_encoding($csvContent, 'UTF-8', 'auto');

// Save file

// Open a file in write mode
            $file = fopen("$fileName", 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
// Add CSV headers
            fputcsv($file, ['ID', 'First Name', 'Last Name', 'Email']);

// Write instructor data to CSV
            foreach ($instructors as $instructor) {
                fputcsv($file, [(int) $instructor->id, $instructor->first_name, $instructor->last_name, $instructor->email]);
            }

// Close the file
            fclose($file);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
        return 0;
    }
}
