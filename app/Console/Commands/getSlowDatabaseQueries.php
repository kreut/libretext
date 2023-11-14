<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class getSlowDatabaseQueries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:slowDatabaseQueries';

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
            $threshhold = 10;
            $num_bad_execution_times = DB::table('execution_times')->where('execution_time', '>', $threshhold)->count();
            $num_execution_times = DB::table('execution_times')->count();
            $bad_execution_times = [];
            $execution_times = DB::table('execution_times')->where('execution_time', '>', $threshhold)->get();
            foreach ($execution_times as $execution_time) {
                if (!isset($bad_execution_times[$execution_time->method])) {
                    $bad_execution_times[$execution_time->method] = [];
                }
                $bad_execution_times[$execution_time->method]['total_count'] = 0;
                if ($execution_time->method !== 'getCourseAssignments') {
                    $assignment_id = json_decode($execution_time->parameters)->assignment_id;
                    if (!isset($bad_execution_times[$execution_time->method][$assignment_id])) {
                        $bad_execution_times[$execution_time->method][$assignment_id] = 0;
                    }
                    $bad_execution_times[$execution_time->method][$assignment_id]++;
                } else {
                    $bad_execution_times[$execution_time->method]['total_count']++;
                }
            }
            // Custom comparison function for usort

// Sorting $bad_execution_times array based on total_count
            arsort($bad_execution_times['getQuestionsToView']);

            dd($bad_execution_times);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
        return 0;
    }
}
