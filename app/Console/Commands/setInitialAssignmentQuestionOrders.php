<?php

namespace App\Console\Commands;

use App\AssignmentSyncQuestion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class setInitialAssignmentQuestionOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assignmentQuestions:setInitialOrder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resets the orders of the assignment questions.';

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
     * @return mixed
     */
    public function handle()
    {
        $assignments = DB::table('assignments')->get();
        DB::beginTransaction();
        $assignmentSyncQuestion = new AssignmentSyncQuestion();
        foreach ($assignments as $assignment) {
            $assignment_questions = DB::table('assignment_question')
                ->where('assignment_id', $assignment->id)
                ->orderBy('assignment_question_id')
                ->get();
            if ($assignment_questions) {
                $ordered_questions = [];
                foreach ($assignment_questions as $assignment_question) {
                    $ordered_questions[] = $assignment_question->question_id;
                }
                $assignmentSyncQuestion->orderQuestions($ordered_questions, $assignment->id);
            }
        }
        DB::commit();
    }
}
