<?php

namespace App\Console\Commands\OneTimers\Nas;

use App\Exceptions\Handler;
use App\Question;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class fixNAs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:nas';

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
            $questions = $Question->getNas();
            DB::beginTransaction();
            foreach ($questions as $key => $question) {
                foreach (['hint', 'notes', 'text_question', 'answer_html', 'solution_html'] as $item) {
                    $question->{$item} = $this->isNA($question->{$item}) ? null : $question->{$item};
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
            echo microtime(true) - $time;
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
    function isNA($html): bool
    {
        return strpos($html, '>N/A</p>') !== false;
    }
}
