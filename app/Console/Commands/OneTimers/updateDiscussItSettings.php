<?php

namespace App\Console\Commands\OneTimers;

use App\AssignmentSyncQuestion;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class updateDiscussItSettings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:discussItSettings';

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
            $assignment_questions = AssignmentSyncQuestion::whereNotNull('discuss_it_settings')->get();
            DB::beginTransaction();
            foreach ($assignment_questions as $assignment_question) {
                echo $assignment_question->id . "\r\n";
                $discuss_it_settings = json_decode($assignment_question->discuss_it_settings);
                $discuss_it_settings->response_modes = ['text', 'audio', 'video'];
                $assignment_question->discuss_it_settings = json_encode($discuss_it_settings);
                $assignment_question->save();

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
