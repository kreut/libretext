<?php

namespace App\Console\Commands\OneTimers;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class deleteLearningTrees extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'delete:learningTrees';

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
            DB::table('learning_tree_histories')
                ->delete();
            DB::table('assignment_question_learning_tree')->delete();
            DB::table('branches')->delete();
            DB::table('learning_tree_successful_branches')->delete();
            DB::table('remediation_submissions')->delete();
            DB::table('learning_tree_node_learning_outcome')->delete();
            DB::table('learning_trees')->delete();
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            echo $e->getMessage();
        }
        return 0;
    }
}
