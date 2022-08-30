<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class getUserCourseFromAssignment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:assignmentInfo {assignment_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'gets the user and course from the assignment';

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
     * @return void
     */
    public function handle()
    {
        try {
            $assignment_id = $this->argument('assignment_id');
            $user_course = DB::table('assignments')
                ->join('courses', 'assignments.course_id', '=', 'courses.id')
                ->join('users', 'courses.user_id', '=', 'users.id')
                ->select(
                    DB::raw('CONCAT(first_name, " ", last_name)'),
                    'email',
                    'courses.name AS course',
                    'assignments.name AS assignment'
                )
                ->where('assignments.id', $assignment_id)
                ->first();
            dd($user_course);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
}
