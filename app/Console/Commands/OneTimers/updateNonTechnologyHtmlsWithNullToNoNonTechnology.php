<?php

namespace App\Console\Commands\OneTimers;

use App\Exceptions\Handler;
use App\Question;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class updateNonTechnologyHtmlsWithNullToNoNonTechnology extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:nonTechnologyHtmlsWithNullToNoNonTechnology';

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
     * @throws Exception
     */
    public function handle()
    {
        try {
            $questions = Question::where('non_technology', 1)
                ->whereNull('non_technology_html')
                ->get();
            DB::beginTransaction();
            echo count($questions) . " questions to fix.\r\n";
            $question_ids = [];
            foreach ($questions as $question) {
                $question_ids[] = $question->id;
                echo $question->id . "\r\n";
                $question->non_technology = 0;
                $question->save();
            }
            echo "Done.";
            echo "(" . implode(', ', $question_ids) . ")";
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            return 1;
        }
        return 0;
    }
}
