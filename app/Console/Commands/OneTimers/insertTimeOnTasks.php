<?php

namespace App\Console\Commands\OneTimers;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class insertTimeOnTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'insert:timeOnTasks';

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
            $time_on_tasks = DB::table('submissions')->where('time_on_task', '<>', 0)->get();
            DB::beginTransaction();
            foreach ($time_on_tasks as $time_on_task) {
                DB::table('assignment_question_time_on_tasks')->insert([
                    'assignment_id' => $time_on_task->assignment_id,
                    'user_id' => $time_on_task->user_id,
                    'question_id' => $time_on_task->question_id,
                    'time_on_task' => $time_on_task->time_on_task
                ]);
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            echo $e->getMessage();
            return 1;
        }
        return 0;
    }
}
