<?php

namespace App\Console\Commands;

use App\Assignment;
use App\AssignmentSyncQuestion;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class copyAssignmentQuestions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'copy:assignmentQuestions {from} {to}';

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
    public function handle()
    {
        $from = $this->argument('from');
        $to = $this->argument('to');
        $from_user_id = $this->_getOwner($from);
        $to_user_id = $this->_getOwner($to);
        if ($from_user_id !== $to_user_id) {
            throw new Exception("$from_user_id is not the same as $to_user_id");
        }

        try {
            $from_assignment_questions = AssignmentSyncQuestion::where('assignment_id', $from)->get();
            DB::beginTransaction();
            foreach ($from_assignment_questions as $from_assignment_question) {
                $to_assignment_question = $from_assignment_question->replicate();
                $to_assignment_question->assignment_topic_id = null;
                $to_assignment_question->assignment_id = $to;
                $to_assignment_question->save();
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            echo $e->getMessage();

        }
        return 0;
    }

    private function _getOwner($id)
    {
        return DB::table('assignments')
            ->join('courses', 'assignments.course_id', '=', 'courses.id')
            ->where('assignments.id', $id)
            ->first()
            ->user_id;
    }
}
