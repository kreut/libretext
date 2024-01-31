<?php

namespace App\Console\Commands\OneTimers;

use App\AssignToGroup;
use App\AssignToTiming;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class fixIssueWithAssignTos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:issueWithAssignTos';

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
        $course_id = 3101;
        $assignments = DB::table('assignments')->where('course_id', $course_id)->get();
        try {
            DB::beginTransaction();
            foreach ($assignments as $assignment) {
                $assign_to_timing = AssignToTiming::where('assignment_id', $assignment->id)->first();
                echo count(AssignToGroup::where('assign_to_timing_id', $assign_to_timing->id)->get());
                AssignToGroup::where('assign_to_timing_id', $assign_to_timing->id)->delete();
                $assign_to_group = new AssignToGroup();
                $assign_to_group->assign_to_timing_id = $assign_to_timing->id;
                $assign_to_group->group = 'course';
                $assign_to_group->group_id = 3101;
                $assign_to_group->save();
            }
            DB::commit();
            return 0;
        } catch (Exception $e) {
            echo $e->getMessage();
            return 1;
        }
    }
}
