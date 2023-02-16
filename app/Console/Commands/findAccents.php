<?php

namespace App\Console\Commands;

use App\Question;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Foundation\Exceptions\Handler;
use Throwable;

class findAccents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'find:accents';

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
     * @param Question $Question
     * @return int
     * @throws Throwable
     */
    public function handle(Question $Question)
    {
        try {
            $bad_questions = [];
            $decoded_entities = ['é', 'à', 'ù', 'ç', 'â', 'ê', 'î', 'ô', 'û', 'ë', 'ï', 'ü'];
            $htlmentities = array_map(function ($char) {
                return htmlentities($char, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }, $decoded_entities);

            $questions = $Question->where('qti_json_type', 'fill_in_the_blank')->get();
            foreach ($questions as $question) {
                $has_htmlentity = false;
                $correct_responses = json_decode($question->qti_json, 1)['responseDeclaration']['correctResponse'];
                foreach ($correct_responses as $correct_response) {
                    foreach ($htlmentities as $htlmentity) {
                        if (!is_array($correct_response['value']) && strpos($correct_response['value'], $htlmentity) !== false) {
                            $has_htmlentity = true;
                        }
                    }
                }
                if ($has_htmlentity) {
                    $bad_questions[] = $question->id;
                }
            }
            if ($bad_questions) {
                $message= "The following questions have fill in the blank decoding issues: " . implode(', ', $bad_questions);
                throw new Exception ($message);
            }
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            return 1;
        }
        return 0;
    }
}
