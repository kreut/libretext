<?php

namespace App\Console\Commands\OneTimers;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class fixLearningTreeAssignmentsAndAssignmentQuestions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:learningTreeAssignmentsAndAssignmentQuestions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates all to the new concept';

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
        $data = ['learning_tree_success_level' => 'tree',
            'learning_tree_success_criteria' => 'time based',
            'min_number_of_successful_branches' => 1,
            'min_number_of_successful_assessments' => 1];
        $num_updated = DB::table('assignments')
            ->where('assessment_type', 'learning tree')
            ->update($data);
        echo "$num_updated assignments were updated\r\n";
        $num_updated = DB::table('assignment_question_learning_tree')
            ->update($data);
        echo "$num_updated assignment questions were updated.";

        return 0;
    }
}
