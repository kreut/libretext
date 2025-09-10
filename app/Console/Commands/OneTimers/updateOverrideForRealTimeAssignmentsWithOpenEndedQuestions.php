<?php

namespace App\Console\Commands\OneTimers;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class updateOverrideForRealTimeAssignmentsWithOpenEndedQuestions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:OverrideForRealTimeAssignmentsWithOpenEndedQuestions';

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
            $num_updated = DB::table('assignment_question')
                ->join('assignments', 'assignments.id', '=', 'assignment_question.assignment_id')
                ->where('assignments.assessment_type', 'real time')
                ->where('assignment_question.open_ended_submission_type', '<>', '0')
                ->where('assignment_question.manual_override_show_open_ended_question', 0)
                ->update([
                    'assignment_question.manual_override_show_open_ended_question' => 1
                ]);
            echo $num_updated;
        } catch (Exception $e) {

            echo $e->getMessage();
        }
        return 0;
    }
}
