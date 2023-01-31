<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class getUserCourseFromCourse extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:courseInfo {course_id}';

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


        public function handle()
    {
        try {
            $course_id = $this->argument('course_id');
            $user_course = DB::table('courses')
                ->join('users', 'courses.user_id', '=', 'users.id')
                ->select(
                    'user_id',
                    DB::raw('CONCAT(first_name, " ", last_name)'),
                    'email',
                    'courses.name AS course'
                )
                ->where('courses.id', $course_id)
                ->first();
            dd($user_course);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
    }
