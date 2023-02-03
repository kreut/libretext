<?php

namespace App\Console\Commands\OneTimers\Qti;

use App\Question;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class addQtiJsonType extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'add:qtiJsonType';

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
            $qti_questions = Question::whereNotNull('qti_json')->get();
            foreach ($qti_questions as $qti_question) {
                $qti_json_type = json_decode($qti_question->qti_json)->questionType;
                $qti_question->qti_json_type = $qti_json_type;
                $qti_question->save();
                echo  $qti_json_type . "\r\n";
            }
            echo count($qti_questions) . " questions have been updated.";
            DB::commit();
            echo 'done';
            return 0;
        } catch (Exception $e) {
            DB::rollBack();
            echo $e->getMessage();
            return 1;
        }
    }
}
