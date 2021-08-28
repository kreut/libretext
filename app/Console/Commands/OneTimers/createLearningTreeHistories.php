<?php

namespace App\Console\Commands\OneTimers;

use App\LearningTree;
use App\LearningTreeHistory;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class createLearningTreeHistories extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:LearningTreeHistories';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates an initial history for the learning trees';

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
        $learning_trees = LearningTree::all();
        try {
            DB::beginTransaction();
            foreach ($learning_trees as $learning_tree) {
                if ($learning_tree->learningTreeHistories->isEmpty()) {
                    $learningTreeHistory = new LearningTreeHistory();
                    $learningTreeHistory->learning_tree_id = $learning_tree->id;
                    $learningTreeHistory->learning_tree = $learning_tree->learning_tree;
                    $learningTreeHistory->save();
                }
            }
            DB::commit();
            echo "success";
        }catch (Exception $e){
            DB::rollback();
            echo "fail";
        }
    }
}
