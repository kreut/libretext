<?php

namespace App\Console\Commands\OneTimers;

use App\Question;
use App\QuestionRevision;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class setMatchStereoTo1 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'set:MatchStereoTo1';

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
            $questions = Question::where('qti_json_type', 'submit_molecule')->get();
            foreach ($questions as $question) {
                $qti_json = json_decode($question->qti_json);
                $qti_json->matchStereo = 1;
                $question->qti_json = json_encode($qti_json);
                $question->save();
                echo $question->id . "\r\n";
            }
            echo "Finished questions\r\n";
            $question_revisions = QuestionRevision::where('qti_json_type', 'submit_molecule')->get();
            foreach ($question_revisions as $question_revision) {
                $qti_json = json_decode($question_revision->qti_json);
                $qti_json->matchStereo = 1;
                $question_revision->qti_json = json_encode($qti_json);
                $question_revision->save();
                echo $question_revision->id . "\r\n";
            }
            echo "Finished question revisions\r\n";
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            echo $e->getMessage();
        }
        return 0;
    }
}
