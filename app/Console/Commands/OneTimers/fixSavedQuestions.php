<?php

namespace App\Console\Commands\OneTimers;

use App\Exceptions\Handler;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class fixSavedQuestions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:SavedQuestions';

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
        $saved_questions = DB::table('saved_questions')->get();
        try {
            DB::beginTransaction();
            foreach ($saved_questions as $saved_question) {
                $assignment_question = DB::table('assignment_question')
                    ->where('id', $saved_question->assignment_question_id)
                    ->first();
                $learning_tree_id_exists = DB::table('assignment_question_learning_tree')
                    ->where('assignment_question_id', $saved_question->assignment_question_id)
                    ->first();
                $learning_tree_id = $learning_tree_id_exists ?
                    $learning_tree_id_exists->learning_tree_id
                    :  null;
                $data = ['question_id' => $assignment_question->question_id,
                    'open_ended_submission_type' => $assignment_question->open_ended_submission_type,
                    'open_ended_text_editor' => $assignment_question->open_ended_text_editor,
                    'learning_tree_id' => $learning_tree_id];
                DB::table('saved_questions')->where('assignment_question_id', $saved_question->assignment_question_id)
                    ->update($data);
            }
            DB::commit();
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            echo $e->getMessage();
            return 1;
        }
        return 0;
    }
}
