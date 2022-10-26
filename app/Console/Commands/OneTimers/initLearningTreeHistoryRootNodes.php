<?php

namespace App\Console\Commands\OneTimers;

use App\LearningTree;
use App\LearningTreeHistory;
use Illuminate\Console\Command;

class initLearningTreeHistoryRootNodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'init:LearningTreeHistoryRootNodes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Adds the library and the page ids to the histories';

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
        $learningTrees = LearningTree::all();
        $learningTreeHistories = LearningTreeHistory::all();
        $learning_trees_by_id = [];

        foreach ($learningTrees as $learningTree){
            $learning_trees_by_id[$learningTree->id]=[
                'root_node_question_id' => $learningTree->root_node_question_id];
        }
        foreach ($learningTreeHistories as $learningTreeHistory){
            $learning_tree = $learning_trees_by_id[$learningTreeHistory->learning_tree_id];
            $learningTreeHistory->root_node_question_id=  $learning_tree['root_node_page_id'];
            $learningTreeHistory->save();
        }

    }
}
