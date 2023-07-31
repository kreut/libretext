<?php

namespace App\Console\Commands\OneTimers;

use App\Branch;
use App\LearningTreeNodeDescription;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class updateDescriptionToLearningTreeNodeDescription extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:DescriptionToLearningTreeNodeDescription';

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
     * @param LearningTreeNodeDescription $learningTreeNodeDescription
     * @return int
     */
    public function handle(LearningTreeNodeDescription $learningTreeNodeDescription): int
    {
        try {
            DB::beginTransaction();
            $branches = Branch::get();
            foreach ($branches as $branch) {
                echo "$branch->description\r\n";
                $learningTreeNodeDescription->where('learning_tree_id', $branch->learning_tree_id)
                    ->where('question_id', $branch->question_id)
                    ->where('user_id', $branch->user_id)
                    ->update(['description' => $branch->description]);
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            echo $e->getMessage();
        }
        return 0;
    }
}
