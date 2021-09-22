<?php

namespace App\Console\Commands\OneTimers;

use App\Question;
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
    public function handle(): int
    {
        $data_shops = DB::table('data_shops')
            ->where('status', 'not fixed')
            ->limit(20000)
            ->get();

        foreach ($data_shops as $key => $data_shop) {
            $enrolled_courses = DB::table('users')
                ->join('enrollments', 'users.id', '=', 'enrollments.user_id')
                ->where('users.email', $data_shop->anon_student_id)
                ->select('course_id')
                ->get()
                ->pluck('course_id');
            if ($enrolled_courses->isNotEmpty()) {
                $assignment_id_exists = DB::table('assignment_question')
                    ->join('assignments', 'assignment_question.assignment_id', '=', 'assignments.id')
                    ->where('question_id', $data_shop->problem_name)
                    ->whereIn('assignments.course_id', $enrolled_courses)
                    ->select('assignments.id', 'assignments.name', 'assignments.assessment_type')
                    ->first();
                $enrolled_message = 'enrolled';
            } else {
                $assignment_id_exists = DB::table('assignment_question')
                    ->where('question_id', $data_shop->problem_name)
                    ->join('assignments', 'assignment_question.assignment_id', '=', 'assignments.id')
                    ->select('assignments.id', 'assignments.name', 'assignments.assessment_type')
                    ->first();
                $enrolled_message = 'not enrolled';
            }
            if ($assignment_id_exists) {
                $assignment_id = $assignment_id_exists->id;
                $assignment_group_info = DB::table('assignments')
                    ->join('assignment_groups', 'assignments.assignment_group_id', '=', 'assignment_groups.id')
                    ->where('assignments.id', $assignment_id)
                    ->select('assignment_groups.assignment_group')
                    ->first();

                $assignment_info = DB::table('assignments')
                    ->join('courses', 'assignments.course_id', '=', 'courses.id')
                    ->join('users', 'courses.user_id', '=', 'users.id')
                    ->where('assignments.id', $assignment_id)
                    ->select('users.first_name',
                        'users.last_name',
                        'courses.name AS course_name',
                        'courses.start_date as course_start_date',
                        'users.email',
                        'courses.school_id',
                        'courses.id AS course_id')
                    ->first();


                $assign_to_timing = DB::table('assign_to_timings')
                    ->join('assign_to_users', 'assign_to_timings.id', '=', 'assign_to_users.assign_to_timing_id')
                    ->where('assignment_id', $assignment_id)
                    ->first();
                $question = Question::find($data_shop->problem_name);

                DB::table('data_shops')
                    ->where('id', $data_shop->id)
                    ->update([
                        'level' => $assignment_id,
                        'level_name' => $assignment_id_exists->name,
                        'level_group' => $assignment_group_info->assignment_group ?: null,
                        'school' => $assignment_info->school_id,
                        'class' => $assignment_info->course_id,
                        'library' => $question->library,
                        'page_id' => $question->page_id,
                        'class_name' => $assignment_info->course_name,
                        'class_start_date' => $assignment_info->course_start_date,
                        'instructor_name' => "$assignment_info->first_name $assignment_info->last_name",
                        'instructor_email' => $assignment_info->email,
                        'number_of_attempts_allowed' => $assignment_id_exists->assessment_type === 'delayed' ? 'unlimited' : '1',
                        'due' => $assign_to_timing ? $assign_to_timing->due : null,
                        'status' => 'fixed']);
                $fixed_message = 'fixed';
            } else {
                DB::table('data_shops')->where('id', $data_shop->id)->update(['status' => "can't fix"]);
                $fixed_message = "can't fix";
            }
            echo "$key $enrolled_message $fixed_message\r\n";
        }
        return 0;
    }
}
