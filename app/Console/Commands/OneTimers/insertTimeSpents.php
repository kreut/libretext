<?php

namespace App\Console\Commands\OneTimers;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class insertTimeSpents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'insert:timeSpents';

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
            $time_spents = DB::table('submissions')->where('time_spent', '<>', 0)->get();
            DB::beginTransaction();
            foreach ($time_spents as $time_spent) {
                DB::table('assignment_question_time_spents')->insert([
                    'assignment_id' => $time_spent->assignment_id,
                    'user_id' => $time_spent->user_id,
                    'question_id' => $time_spent->question_id,
                    'time_spent' => $time_spent->time_spent
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
