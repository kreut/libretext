<?php

namespace App\Console\Commands;

use App\Assignment;
use App\Exceptions\Handler;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

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
            $counts = DB::select(DB::raw("
                            SELECT COUNT(*) AS count, user_id
                            FROM assign_to_users
                            INNER JOIN assign_to_timings
                            ON (assign_to_users.assign_to_timing_id = assign_to_timings.id)
                            WHERE user_id IN (SELECT user_id FROM enrollments WHERE course_id = 45)
                            AND assignment_id IN (select id FROM assignments WHERE assignments.course_id = 45)
                            GROUP BY user_id"));
            $assign_to_by_users = [];
            foreach ($counts as $count) {
                    $assign_to_by_users[$count->user_id] = $count->count;
            }
            $count = $assign_to_by_users[$count->user_id];
            $problem_users = [];
            foreach ($assign_to_by_users as $user_id => $user_count) {
                if ($count !== $user_count && $user_id !== 590) {
                    $problem_users = ['user_id' => $user_id, 'user_count' => $user_count];
                }
            }
            if ($problem_users){
                throw new Exception("Count mismatch in assign tos.  Should be $count. " . print_r($problem_users,true));

            }
            echo "No issues";
        } catch (Exception $e) {

            $h = new Handler(app());
            $h->report($e);

        }

    }
}
