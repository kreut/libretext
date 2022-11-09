<?php

namespace App\Console\Commands\OneTimers\NasAndDollarSigns;

use App\Exceptions\Handler;
use App\Question;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class createNAsAndDollarSigns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:nasAndDollarSigns';

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
            $questions =$Question->getNasAndDollarSigns();
            DB::beginTransaction();
            foreach ($questions as $key => $question) {
                echo "$key\r\n";
                if (!DB::table('question_nas_and_dollar_signs')
                    ->where('question_id', $question->id)
                    ->first()) {
                    DB::table('question_nas_and_dollar_signs')
                        ->insert(['question_id' => $question->id,
                            'answer_html' => $question->answer_html,
                            'solution_html' => $question->solution_html,
                            'hint' => $question->hint,
                            'notes' => $question->notes,
                            'text_question' => $question->text_question,
                            'created_at' => now(),
                            'updated_at' => now()]);
                }
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
}
