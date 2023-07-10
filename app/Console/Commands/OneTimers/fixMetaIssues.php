<?php

namespace App\Console\Commands\OneTimers;

use App\Question;
use App\QuestionRevision;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class fixMetaIssues extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:metaIssues';

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
            $revised_question_ids = DB::table('question_revisions')
                ->select('question_id')
                ->groupBy('question_id')
                ->get()
                ->pluck('question_id')
                ->toArray();

            $latest_question_revision_id_by_question_id = [];
            foreach ($revised_question_ids as $revised_question_id) {
                $question = Question::find($revised_question_id);
                $latest_revision_id = $question->latestQuestionRevision('id');
                $latest_question_revision_id_by_question_id[$question->id] = $latest_revision_id;
            }
            foreach ($latest_question_revision_id_by_question_id as $question_id => $latest_revision_id) {
                $question = Question::find($question_id);
                $latest_revision = QuestionRevision::find($latest_revision_id);
                if ($question->author !== $latest_revision->author || $question->source_url !== $latest_revision->source_url) {
                    $latest_revision->author = $question->author;
                    $latest_revision->source_url = $question->source_url;
                    $latest_revision->save();
                    echo $question_id . "\r\n";
                }

            }
        }  catch(Exception $e){
                echo $e->getMessage();
                return 1;
            }
        return 0;
    }
}
