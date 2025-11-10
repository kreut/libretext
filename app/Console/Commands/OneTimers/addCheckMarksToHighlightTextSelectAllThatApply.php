<?php

namespace App\Console\Commands\OneTimers;

use App\Question;
use App\QuestionRevision;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class addCheckMarksToHighlightTextSelectAllThatApply extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'add:CheckMarksToHighlightTextSelectAllThatApply';

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
            DB::beginTransaction();
            $questions = Question::whereIn('qti_json_type', ['highlight_text', 'multiple_response_select_all_that_apply'])->get();
            echo count($questions) . " will be updated.\r\n";
            $num_revisions = 0;
            foreach ($questions as $question) {
                $question_revisions = QuestionRevision::where('question_id', $question->id)->get();
                $num_revisions += count($question_revisions);
                foreach ($question_revisions as $question_revision) {
                    $this->_addCheckMarks($question_revision);
                }
                $this->_addCheckMarks($question);
            }
            echo "$num_revisions question revisions will be updated.\r\n";
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            echo $e->getMessage();
        }
        return 0;
    }

    private function _addCheckMarks($question)
    {
        $qti_json = json_decode($question->qti_json, 1);
        $qti_json['check_marks'] = 'correctly checked answers and correctly unchecked incorrect answers';
        $qti_json = json_encode($qti_json);
        $question->qti_json = $qti_json;
        $question->save();
    }
}
