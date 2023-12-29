<?php

namespace App\Console\Commands\OneTimers;

use App\Question;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class fixMaxVersionIssue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:maxVersionIssue';

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
            $questionsWithSingleMinVersion = Question::select('id')
                ->selectSub('MAX(version)', 'max_version')
                ->groupBy('id')
                ->havingRaw('COUNT(DISTINCT max_version) = 1 AND max_version > 1')
                ->get()
                ->pluck('id')
                ->toArray();
            $questions = Question::whereIn('id', $questionsWithSingleMinVersion)->get();
            foreach ($questions as $question) {
                if (!$question->clone_source_id) {
                    echo $question->id . ' ' . $question->technology . ' ' . $question->technology_id . ' ' . $question->created_at . "\r\n";
                }
            }
        } catch (Exception $e) {
            echo $e->getMessage();


        }
        return 0;
    }
}
