<?php

namespace App\Console\Commands\SearchImprovementFixes\TitleFixes;

use App\Libretext;
use App\QuestionBetterTitle;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class betterTitles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'better:titles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'For imathas and webwork, use the path to create better titles';

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
        $error_count = 0;
        $questions_better_titles = DB::table('question_better_titles')
            ->select('id')
            ->get()
            ->pluck('id')
            ->toArray();
        $questions_with_url = DB::table('questions')
            ->whereIn('technology', ['imathas', 'webwork'])
            ->where('version', 1)
            ->where('url', '<>', null)
            ->select('library', 'title', 'page_id', 'id', 'url')
            ->get();
        $num_questions = count($questions_with_url);
        foreach ($questions_with_url as $key => $question) {
            if (!in_array($question->id, $questions_better_titles)) {
                $questionBetterTitle = new QuestionBetterTitle();
                $questionBetterTitle->id = $question->id;
                $questionBetterTitle->title = $question->title;
                $questionBetterTitle->url = $question->url;
                $questionBetterTitle->save();
            }
        }


        $questions_with_no_url = DB::table('questions')
            ->whereIn('technology', ['imathas', 'webwork'])
            ->where('version', 1)
            ->where('url', null)
            ->where('library','<>','adapt')
            ->select('library', 'title', 'page_id', 'id', 'url')
            ->get();
        $num_questions = count($questions_with_no_url);
        foreach ($questions_with_no_url as $key => $question) {
            try {
                $url = 'none';
                if (!in_array($question->id, $questions_better_titles)) {
                    $Libretext = new Libretext(['library' => $question->library]);
                    $contents = $Libretext->getPrivatePage('info', $question->page_id);
                    $url = $contents->path->{'#text'};
                    $questionBetterTitle = new QuestionBetterTitle();
                    $questionBetterTitle->id = $question->id;
                    $questionBetterTitle->title = $question->title;
                    $questionBetterTitle->url = $url;
                    $questionBetterTitle->save();
                    echo $num_questions - $key . ' ' . $url . "\r\n";
                }

            } catch (Exception $e) {
                $error_count++;
                echo "Error: $question->id" . $e->getMessage() . "\r\n";
            }
        }
        echo $error_count;
    }
}
