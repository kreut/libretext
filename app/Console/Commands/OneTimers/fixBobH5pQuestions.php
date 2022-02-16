<?php

namespace App\Console\Commands\OneTimers;

use App\Question;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class fixBobH5pQuestions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:BobH5PQuestions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Moves questions to Elena';

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
            Question::where('question_editor_user_id', 2000)
                ->where('author', 'rebelford')
                ->where('created_at', '>', Carbon::now()->subDays(14))
                ->update(['question_editor_user_id' => 92, 'folder_id' => 140]);
            return 0;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
        return 1;
    }
}
