<?php

namespace App\Console\Commands\OneTimers;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class fixSubmitWork extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:submitWork';

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
            $badAssignmentQuestions =
                DB::table('assignments')
                    ->join('courses', 'assignments.course_id', '=', 'courses.id')
                    ->join('assignment_question', 'assignments.id', '=', 'assignment_question.assignment_id')
                    ->where('assignments.can_submit_work', 0)
                    ->where('assignment_question.can_submit_work_override', 1)
                    ->select(
                        'assignment_question.id as assignment_question_id',
                        'assignments.name as assignment_name',
                        'courses.name as course_name'
                    )
                    ->get();
            $count = 0;
            foreach ($badAssignmentQuestions as $bad) {
                $updated = DB::table('assignment_question')
                    ->where('id', $bad->assignment_question_id)
                    ->update([
                        'can_submit_work_override' => null,
                    ]);
                $count += $updated;
                echo "{$bad->assignment_name} — {$bad->course_name}\n";
            }
            echo "$count updated";
        } catch (Exception $e) {
            echo $e->getMessage();

        }
        return 0;
    }
}
