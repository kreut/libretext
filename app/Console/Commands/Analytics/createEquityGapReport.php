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
            $classes = DB::table('data_shops')->select('class')
                ->groupBy('class')
                ->get()
                ->pluck('class')
                ->toArray();

            $columns = ['Campus name',
                'Semester/Quarter',
                'Year of Participation',
                'Course Title',
                'Number of Course Sections',
                'Number of enrolled students'];
            $csv = fopen("/Users/franciscaparedes/Downloads/learning_lab_report.csv", 'w');
            fputcsv($csv, $columns);
            foreach ($classes as $class) {
                $campus_name = DB::table('data_shops')
                    ->join('schools', 'data_shops.school', '=', 'schools.id')
                    ->select('schools.name')
                    ->where('class', $class)
                    ->first()
                    ->name;
                $start_date = DB::table('data_shops')
                    ->select('class_start_date')
                    ->where('class', $class)
                    ->first()
                    ->class_start_date;
                $date = new Carbon($start_date);
                $year = $date->year;
                $month = $date->month;

                $class_name = DB::table('data_shops')
                    ->select('class_name')
                    ->where('class', $class)
                    ->first()
                    ->class_name;
                $course = DB::table('courses')
                    ->where('id', $class)
                    ->first();

                $num_sections = 'Unknown';
                $term = $month;
                if ($course) {
                    $num_sections = DB::table('sections')->where('course_id', $course->id)->count();
                    $term = $course->term;
                }
                $number_students_enrolled = DB::table('data_shops')
                    ->where('class', $class)
                    ->count(DB::raw('DISTINCT anon_student_id'));

                fputcsv($csv, [$campus_name, $term, $year, $class_name, $num_sections, $number_students_enrolled]);
            }
            fclose($csv);
            echo "done";
        } catch (Exception $e) {
            echo $e->getMessage();
        }
        return 0;
    }
}
