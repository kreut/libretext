<?php

namespace App\Console\Commands\DuplicateFixes\Versions;

use App\Question;
use App\QuestionVersion;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class addVersionToQuestionVersions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'add:versionToQuestionVersions';

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
        echo Carbon::now() . "\r\n";
        try {
            $technologies = ['h5p', 'webwork', 'imathas'];
            foreach ($technologies as $technology) {
                $questions = DB::table('questions')
                    ->where('technology', $technology)
                    ->select('technology_id', DB::raw('COUNT(*) AS count'))
                    ->groupBy('technology_id')
                    ->having('count', '>', 1)
                    ->get();
                $technology_ids = [];
                foreach ($questions as $question) {
                    $technology_ids[] = $question->technology_id;
                }
                foreach ($technology_ids as $technology_id) {
                    $questions = Question::where('technology_id', $technology_id)
                        ->where('technology', $technology)
                        ->orderBy('id')
                        ->get();
                    if ($questions->isNotEmpty()) {
                        foreach ($questions as $key => $question) {
                            $questionVersion = new QuestionVersion();
                            $questionVersion->version = $key + 1;
                            $questionVersion->question_id = $question->id;
                            $questionVersion->save();
                        }
                    }
                }
            }
        } catch (Exception $e) {
            DB::rollback();
            echo $e->getMessage();
        }
        echo Carbon::now();
        return 0;
    }
}
