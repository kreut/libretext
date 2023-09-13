<?php

namespace App\Console\Commands\OneTimers;

use App\User;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class updateLmsGradePassbackToManual extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:LmsGradePassbackToManual';

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
            DB::beginTransaction();
            $users = User::where(function ($query) {
                $query->where('first_name', 'Adriana')
                    ->orWhere('first_name', 'Cecilia')
                    ->orWhere('first_name', 'Beatriz');
            })
                ->where('email', 'like', '%estrella%')
                ->where('role', 2)
                ->get();
            foreach ($users as $user) {
                echo $user->first_name . ' ' . $user->last_name . "\r\n";
            }
            foreach ($users as $user) {
                $courses = DB::table('courses')->where('user_id', $user->id)->get();
                foreach ($courses as $course) {
                    echo $course->name . "\r\n";
                    if ($course->lms) {
                        DB::table('assignments')
                            ->where('course_id', $course->id)
                            ->where('assessment_type', 'delayed')
                            ->update(['lms_grade_passback' => 'manual', 'updated_at' => now()]);
                    }
                }
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            echo $e->getMessage();
        }
        return 0;
    }
}
