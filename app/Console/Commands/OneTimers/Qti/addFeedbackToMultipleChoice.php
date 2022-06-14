<?php

namespace App\Console\Commands\OneTimers\Qti;

use App\QtiImport;
use App\Question;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class addFeedbackToMultipleChoice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'add:feedbackToMultipleChoice';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fixes current multiple choice which have feedback';

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
    public function handle(QtiImport $qtiImport)
    {
        $with_feedbacks = DB::table('qti_imports')
            ->join('questions', 'qti_imports.question_id', '=', 'questions.id')
            ->where('xml', 'LIKE', '%multiple_choice%')
            ->where('xml', 'LIKE', '%feedback%')
            ->select('question_id', 'qti_json', 'xml')
            ->get();
        try {
            foreach ($with_feedbacks as $with_feedback) {
                $question_id = $with_feedback->question_id;
                $xml = \Safe\simplexml_load_string($with_feedback->xml);
                $simple_choice_array = json_decode($with_feedback->qti_json, true);
                $xml_array = json_decode(json_encode($xml), true);
                $simple_choice_array = $qtiImport->getFeedBack($xml_array,$simple_choice_array);
                Question::where('id', $question_id)->update(['qti_json' => json_encode($simple_choice_array)]);
            }
            DB::commit();
            echo 'done';
            return 0;
        } catch (Exception $e) {
            DB::rollBack();
        }
        echo $e->getMessage();
        return 1;
    }
}

