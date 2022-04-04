<?php

namespace App\Console\Commands\OneTimers;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class fixNumberOfResets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:numberOfResets';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sets the number of resets to 1 for current learning tree assignments';

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
            $num_updated = DB::table('assignments')
                ->where('assessment_type', 'learning tree')
                ->where('learning_tree_success_criteria', 'branch')
                ->update(['number_of_resets'=> 1]);
            echo "Assignments updated: $num_updated\r\n";
            $num_updated = DB::table('assignment_question_learning_tree')
                ->where('learning_tree_success_criteria', 'branch')
                ->update(['number_of_resets'=> 1]);
            echo "Assignment questions updated: $num_updated\r\n";
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            echo $e->getMessage();
            return 1;
        }
        return 0;
    }
}
