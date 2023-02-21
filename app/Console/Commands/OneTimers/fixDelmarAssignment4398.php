<?php

namespace App\Console\Commands\OneTimers;

use App\Submission;
use App\User;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class fixDelmarAssignment4398 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:delmarAssignment4398';

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
            //2/20
            $all_auto_graded_submissions = Submission::where('assignment_id', 4398)
                ->where('question_id', 98330)
                ->get('user_id')
                ->pluck('user_id')
                ->toArray();
            $submission_files_user_ids = DB::table('submission_files')
                ->where('assignment_id', 4398)
                ->where('question_id', 98330)
                ->get('user_id')
                ->pluck('user_id')
                ->toArray();

            echo "Did not submit any auto graded\r\n";
            foreach ($submission_files_user_ids as $submission_files_user_id) {
                if (!in_array($submission_files_user_id, $all_auto_graded_submissions)) {
                    echo User::find($submission_files_user_id)->first_name . ' ' . User::find($submission_files_user_id)->last_name . "\r\n";
                }
            }

            echo "Submitted auto-graded and PDF but got lower than 11\r\n";
            $auto_graded_submissions = Submission::where('assignment_id', 4398)
                ->where('question_id', 98330)
                ->whereIn('user_id', $submission_files_user_ids)
                ->where('score', '<', '11')
                ->get();
            foreach ($auto_graded_submissions as $auto_graded_submission) {
                echo User::find($auto_graded_submission->user_id)->first_name . ' ' . User::find($auto_graded_submission->user_id)->last_name . "\r\n";
            }
            return 0;
        } catch (Exception $e) {
            DB::rollback();
            echo $e->getMessage();
            return 1;

        }

    }
}
