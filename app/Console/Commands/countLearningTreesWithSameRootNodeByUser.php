<?php

namespace App\Console\Commands;

use App\Exceptions\Handler;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class countLearningTreesWithSameRootNodeByUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'count:LearningTreesWithSameRootNodeByUser';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Counts the learning trees with the same IDs by user';

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
     * @throws Exception
     */
    public function handle()
    {
        $current_duplicates = '{"Delmar Larsen":{"274162":2,"278390":2},"Instructor Kean":{"93565":2},"Larry Mink":{"274244":5,"309414":2},"Gregory Allen":{"124126":2},"James Paradiso":{"98787":5,"1":2}}';
        try {
            $learning_trees = DB::table('learning_trees')
                ->join('users', 'learning_trees.user_id', '=', 'users.id')
                ->select('root_node_page_id', DB::raw('CONCAT(first_name, " " , last_name) AS instructor'))
                ->get();
            $learning_trees_by_user_id = [];

            foreach ($learning_trees as $learning_tree) {
                if (!isset($learning_trees_by_user_id[$learning_tree->instructor])) {
                    $learning_trees_by_user_id[$learning_tree->instructor] = [];
                }
                if (!isset($learning_trees_by_user_id[$learning_tree->instructor][$learning_tree->root_node_page_id])) {
                    $learning_trees_by_user_id[$learning_tree->instructor][$learning_tree->root_node_page_id] = 1;
                } else {
                    $learning_trees_by_user_id[$learning_tree->instructor][$learning_tree->root_node_page_id] = $learning_trees_by_user_id[$learning_tree->instructor][$learning_tree->root_node_page_id] + 1;
                }
            }

            foreach ($learning_trees_by_user_id as $instructor => $root_page_nodes) {
                foreach ($root_page_nodes as $root_page_node => $count) {
                    if ($count === 1) {
                        unset($learning_trees_by_user_id[$instructor][$root_page_node]);
                    }
                }
            }

            foreach ($learning_trees_by_user_id as $instructor => $root_page_nodes) {
                if (!$root_page_nodes) {
                    unset($learning_trees_by_user_id[$instructor]);
                }
            }
            if (json_encode($learning_trees_by_user_id) !== $current_duplicates) {
                throw new Exception("Current learning tree duplicates are not the same.");
            }
            return 0;
        } catch (Exception $e) {
            echo $e->getMessage();
            $h = new Handler(app());
            $h->report($e);
        }
        return 1;
    }
}
