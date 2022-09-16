<?php

namespace App\Console\Commands\Analytics;

use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class createEquityGapReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:equityGapReport';

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
            $course_ids = DB::table('data_shops')->select('course_id')
                ->groupBy('course_id')
                ->get()
                ->pluck('course_id')
                ->toArray();

            $columns = ['Campus name',
                'Semester/Quarter',
                'Year of Participation',
                'Course Title',
                'Number of Course Sections',
                'Number of enrolled students'];
            $csv = fopen("/Users/franciscaparedes/Downloads/learning_lab_report.csv", 'w');
            fputcsv($csv, $columns);
            foreach (  $course_ids as $course_id) {
                $campus_name = DB::table('data_shops')
                    ->join('schools', 'data_shops.school', '=', 'schools.id')
                    ->select('schools.name')
                    ->where('course_id', $course_id)
                    ->first()
                    ->name;
                $start_date = DB::table('data_shops')
                    ->select('course_start_date')
                    ->where('course_id', $course_id)
                    ->first()
                    ->course_start_date;
                $date = new Carbon($start_date);
                $year = $date->year;
                $month = $date->month;

                $course_name = DB::table('data_shops')
                    ->select('course_name')
                    ->where('course_id', $course_id)
                    ->first()
                    ->course_name;
                $instructor = DB::table('data_shops')
                    ->select('instructor_name')
                    ->where('course_id', $course_id)
                    ->first()
                    ->instructor_name;
                $course = DB::table('courses')
                    ->where('id', $course_id)
                    ->first();

                $num_sections = 'Unknown';
                $term = $month;
                if ($course) {
                    $num_sections = DB::table('sections')->where('course_id', $course->id)->count();
                    $term = $course->term;
                }
                $number_students_enrolled = DB::table('data_shops')
                    ->where('course_id', $course_id)
                    ->count(DB::raw('DISTINCT anon_student_id'));
                if ($instructor === 'Instructor Kean') {
                    continue;
                }
                fputcsv($csv, [$campus_name, $term, $year, $course_name, $instructor, $num_sections, $number_students_enrolled]);
            }
            fclose($csv);
            echo "done";
        } catch (Exception $e) {
            echo $e->getMessage();
        }
        return 0;
    }
}
