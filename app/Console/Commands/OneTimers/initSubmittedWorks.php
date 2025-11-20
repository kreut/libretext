<?php

namespace App\Console\Commands\OneTimers;

use App\Assignment;
use App\Submission;
use App\SubmittedWork;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class initSubmittedWorks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'init:SubmittedWorks';

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
            $submitted_works = Submission::whereNotNull('submitted_work')->get();
            foreach ($submitted_works as $key => $submitted_work) {
                echo count($submitted_works) - $key . "\r\n";
                if (!DB::table('submitted_works')
                    ->where('assignment_id', $submitted_work->assignment_id)
                    ->where('question_id', $submitted_work->question_id)
                    ->where('user_id', $submitted_work->user_id)
                    ->exists()) {
                    $submittedWork = new SubmittedWork();
                    $submittedWork->assignment_id = $submitted_work->assignment_id;
                    $submittedWork->question_id = $submitted_work->question_id;
                    $submittedWork->user_id = $submitted_work->user_id;
                    $submittedWork->submitted_work = $submitted_work->submitted_work;
                    $submittedWork->created_at = $submittedWork->updated_at = $submitted_work->submitted_work_at;
                    $submittedWork->format = 'file';
                    $submittedWork->save();
                }
            }
            $assignments = Assignment::where('can_submit_work', 1)->get();
            foreach ($assignments as $assignment) {
                $assignment->submitted_work_format = '["file"]';
                $assignment->submitted_work_policy = 'optional';
                $assignment->save();
            }
            echo 'Done!';
            DB::commit();
            return 0;
        } catch (Exception $e) {
            DB::rollback();
            echo $e->getMessage();
        }
        return 1;
    }
}
