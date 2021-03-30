<?php

namespace App\Console\Commands;

use App\Assignment;
use App\Exceptions\Handler;
use Exception;
use Illuminate\Console\Command;

class checkAssignTos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:AssignTos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Double check assign to counts for Delmar's class as as part of troubleshooting";

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
     * @throws Exception
     */
    public function handle()
    {
        try {
            $counts = Assignment::join('assign_to_timings', 'assignments.id', '=', 'assign_to_timings.assignment_id')
                ->join('assign_to_users', 'assign_to_timings.id', '=', 'assign_to_users.assign_to_timing_id')
                ->where('course_id', 45)
                ->get();
            $assign_to_by_users = [];
            foreach ($counts as $count) {
                if (!isset($assign_to_by_users[$count->user_id])) {
                    $assign_to_by_users[$count->user_id] = 1;
                } else {
                    $assign_to_by_users[$count->user_id]++;
                }
            }
            $count = $assign_to_by_users[$count->user_id];
            $problem_users = [];
            foreach ($assign_to_by_users as $user_id => $user_count) {
                if ($count !== $user_count) {
                    $problem_users = ['user_id' => $user_id, 'user_count' => $user_count];
                }
            }
            if ($problem_users){
                throw new Exception("Count mismatch in assign tos.  Should be $count. " . print_r($problem_users,true));

            }
        } catch (Exception $e) {

            $h = new Handler(app());
            $h->report($e);


        }
    }
}
