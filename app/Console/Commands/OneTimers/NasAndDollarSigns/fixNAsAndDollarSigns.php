<?php

namespace App\Console\Commands\OneTimers\NasAndDollarSigns;

use App\Exceptions\Handler;
use App\Question;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class fixNAsAndDollarSigns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:nasAndDollarSigns';

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
    public function handle(Question $Question)
    {
        $time = microtime(true);
        try {
            $questions = $Question->getNasAndDollarSigns();
            DB::beginTransaction();
            foreach ($questions as $question) {
                foreach (['hint', 'notes', 'text_question', 'answer_html', 'solution_html'] as $item) {
                    $question->{$item} = $this->isNAOrDollarSign($question->{$item}) ? null : $question->{$item};
                }
                if (!$question->solution_html && $question->answer_html) {
                    $question->solution_html = $question->answer_html;
                    $answer_identifier = '<span id="Answer"></span><h2 class="editable">Answer</h2>';
                    if (strpos($question->solution_html, $answer_identifier) !== false) {
                        $question->solution_html = str_replace($answer_identifier, '<span id="Solution"></span><h2 class="editable">Solution</h2>', $question->solution_html);
                        echo "REPLACE " . $question->id . "\r\n";
                    }
                }
                $question->save();
            }
            echo microtime(true) - $time . "\r\n";
            echo count($questions) . " questions";
            DB::commit();
            return 0;
        } catch (Exception $e) {
            echo $e->getMessage();
            $h = new Handler(app());
            $h->report($e);
            return 1;
        }

    }

    /**
     * @param $html
     * @return bool
     */
    function isNAOrDollarSign($html): bool
    {
        return strpos($html, '>N/A</p>') !== false || strpos($html, '<p>$</p>') !== false;
    }
}
