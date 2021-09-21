<?php

namespace App\Console\Commands\OneTimers;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class fixDataShopLevelClassAndAddDueDate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:dataShopLevelClassAndDueDates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'The class and levels were not correct.  Add the due dates.';

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
        $data_shops = DB::table('data_shops')
            ->where('status', 'not fixed')
            ->limit(30000)
            ->get();

        foreach ($data_shops as $key => $data_shop) {
            echo $key . "\r\n";
            $enrolled_courses = DB::table('users')
                ->join('enrollments', 'users.id', '=', 'enrollments.user_id')
                ->where('users.email', $data_shop->anon_student_id)
                ->select('course_id')
                ->get()
                ->pluck('course_id');
            if (!$enrolled_courses) {
                DB::table('data_shops')->where('id', $data_shop->id)->update(['status'=>"can't fix"]);
                continue;
            }
            $assignment_id_exists = DB::table('assignment_question')
                ->join('assignments', 'assignment_question.assignment_id', '=', 'assignments.id')
                ->where('question_id', $data_shop->problem_name)
                ->whereIn('assignments.course_id', $enrolled_courses)
                ->select('assignments.id')
                ->first();

            if ($assignment_id_exists) {
                $assignment_id = $assignment_id_exists->id;
                $assignment_info = DB::table('assignments')
                    ->join('courses', 'assignments.course_id', '=', 'courses.id')
                    ->where('assignments.id', $assignment_id)
                    ->first();

                $school = $assignment_info->school_id;
                $course_id = $assignment_info->course_id;
                $assign_to_timing = DB::table('assign_to_timings')
                    ->join('assign_to_users', 'assign_to_timings.id', '=', 'assign_to_users.assign_to_timing_id')
                    ->where('assignment_id', $assignment_id)
                    ->first();

                DB::table('data_shops')
                    ->where('id', $data_shop->id)
                    ->update([
                    'level' => $assignment_id,
                    'school' => $school,
                    'class' => $course_id,
                    'due' => $assign_to_timing->due,
                    'status' => 'fixed']);

            } else {
                DB::table('data_shops')->where('id', $data_shop->id)->update(['status'=>"can't fix"]);

            }
        }
        return 0;
    }
}
