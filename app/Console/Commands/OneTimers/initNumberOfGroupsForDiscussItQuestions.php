<?php

namespace App\Console\Commands\OneTimers;

use App\AssignmentSyncQuestion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class initNumberOfGroupsForDiscussItQuestions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'init:numberOfGroupsForDiscussItQuestions';

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
            $assignment_questions = AssignmentSyncQuestion::whereNotNull('discuss_it_settings')->get();
            foreach ($assignment_questions as $assignment_question) {
                $discuss_it_settings = json_decode($assignment_question->discuss_it_settings);
                $discuss_it_settings->number_of_groups = "1";
                $assignment_question->discuss_it_settings = json_encode($discuss_it_settings);
                $assignment_question->save();
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            echo $e->getMessage();
        }
        return 0;
    }
}
