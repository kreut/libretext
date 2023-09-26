<?php

namespace App\Console\Commands\OneTimers;

use App\Assignment;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class fixMissingAutomaticManualPassback extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:missingAutomaticManualPassback';

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
            $null_lms_grade_passbacks = DB::table('courses')
                ->join('assignments', 'courses.id', '=', 'assignments.course_id')
                ->join('users', 'courses.user_id', '=', 'users.id')
                ->where('courses.lms', 1)
                ->whereNull('assignments.lms_grade_passback')
                ->select(DB::raw('CONCAT(first_name, " " , last_name) AS instructor'),
                    'courses.name AS course_name', 'assignments.name AS assignment_name',
                    'assignments.id AS assignment_id')
                ->get();
            foreach ($null_lms_grade_passbacks as $null_lms_grade_passback) {
                echo $null_lms_grade_passback->instructor . ' ' . $null_lms_grade_passback->course_name . ' ' . $null_lms_grade_passback->assignment_name . "\r\n";
                DB::table('passback_by_assignments')->insert([
                    'assignment_id' => $null_lms_grade_passback->assignment_id,
                    'status' => 'manual_passback',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                DB::table('null_lms_grade_passbacks')->insert([
                    'assignment_id' => $null_lms_grade_passback->assignment_id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                Assignment::where('id', $null_lms_grade_passback->assignment_id)->update(['lms_grade_passback' => 'automatic']);
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            echo $e->getMessage();

        }
        return 0;
    }
}
