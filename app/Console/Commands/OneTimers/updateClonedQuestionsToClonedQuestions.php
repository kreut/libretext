<?php

namespace App\Console\Commands\OneTimers;

use App\SavedQuestionsFolder;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class updateClonedQuestionsToClonedQuestions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:clonedQuestionsToClonedQuestions';

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
            $saved_question_folders = SavedQuestionsFolder::where('name', 'Cloned questions')
                ->get();
            DB::beginTransaction();
            foreach ($saved_question_folders as $folder) {
                $folder->name = 'Cloned Questions';
                $folder->save();
            }
            DB::commit();
            echo count($saved_question_folders) . " were updated.";
            return 0;
        } catch (Exception $e) {
            DB::rollback();
            echo $e->getMessage();
            return 1;
        }
    }
}
