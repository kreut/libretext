<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class getTareaLibreSchools extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:tareaLibreSchools';

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
        /* $schoolNames = DB::table('assignment_question')
             ->join('assignments', 'assignment_question.assignment_id', '=', 'assignments.id')
             ->join('courses', 'assignments.course_id', '=', 'courses.id')
             ->join('schools', 'courses.school_id', '=', 'schools.id')
             ->whereIn('assignment_question.question_id', function ($query) {
                 $query->select('id')
                     ->from('questions')
                     ->where('question_editor_user_id', 2743)
                     ->orWhereIn('clone_source_id', function ($subQuery) {
                         $subQuery->select('id')
                             ->from('questions')
                             ->where('question_editor_user_id', 2743);
                     });
             })
             ->distinct()
             ->pluck('schools.name');*/
        $courseTotals = DB::table('assignment_question')
            ->join('assignments', 'assignment_question.assignment_id', '=', 'assignments.id')
            ->join('courses', 'assignments.course_id', '=', 'courses.id')
            ->join('schools', 'courses.school_id', '=', 'schools.id')
            ->join('enrollments', 'courses.id', '=', 'enrollments.course_id')
            ->join('users', 'enrollments.user_id', '=', 'users.id') // Join with users table
            ->where('users.fake_student', '=', 0) // Exclude fake users
            ->whereIn('assignment_question.question_id', function ($query) {
                $query->select('id')
                    ->from('questions')
                    ->where('question_editor_user_id', 2743)
                    ->orWhereIn('clone_source_id', function ($subQuery) {
                        $subQuery->select('id')
                            ->from('questions')
                            ->where('question_editor_user_id', 2743);
                    });
            })
            ->select(
                'schools.name as school_name',
                DB::raw('COUNT(DISTINCT enrollments.id) as enrollment_totals')
            )
            ->groupBy('schools.name')
            ->orderBy('enrollment_totals', 'desc')
            ->get();
        $totalsBySchool = [];
        foreach ($courseTotals as $row) {
            $schoolName = $row->school_name;
            $enrollmentTotal = $row->enrollment_totals;

            if (!isset($totalsBySchool[$schoolName])) {
                $totalsBySchool[$schoolName] = 0;
            }

            $totalsBySchool[$schoolName] += $enrollmentTotal;
        }
        $this->info('sorting');
// Optional: Sort by totals in descending order
        arsort($totalsBySchool);
        $this->info('writing');
        $csvFileName = 'enrollments_by_school.csv';
        $filePath = storage_path('app/' . $csvFileName);

        $csvHeader = ['School Name', 'Total Enrollments'];
        $handle = fopen($filePath, 'w');
        fputcsv($handle, $csvHeader);

        foreach ($totalsBySchool as $schoolName => $totalEnrollments) {
            fputcsv($handle, [$schoolName, $totalEnrollments]);
        }

        fclose($handle);

// Optional: Store the file in Laravel storage for download
        dd('done');
        return 0;
    }
}
