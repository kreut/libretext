<?php

namespace App\Console\Commands;

use App\SubmissionFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class populateAssignmentWithCombinedPdfSubmission extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'populate:AssignmentWithCombinedPdfSubmission';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'If there is one submission file, use it for the others.';

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
        $assignment_id = 543;
        $count = 0;
        $assignment_question_ids = [100016, 100017, 100018, 100019, 100020, 100021, 100022];
        DB::beginTransaction();
        $combined_submission_files = SubmissionFile::where('assignment_id', $assignment_id)
            ->where('type', 'a')
            ->get();
        $combined_submission_files_by_user = [];
        foreach ($combined_submission_files as $combined_file) {
            $combined_submission_files_by_user [$combined_file->user_id] = $combined_file;
        }

        foreach ($combined_submission_files_by_user as $user_id => $combined_submission_file) {
            foreach ($assignment_question_ids as $question_id) {
                $submission_file = DB::table('submission_files')
                    ->where('assignment_id', $assignment_id)
                    ->where('type', 'q')
                    ->where('user_id', $user_id)
                    ->where('question_id', $question_id)
                    ->first();
                if (!$submission_file) {
                    $individual_submission_file = $combined_submission_file->replicate()->fill([
                        'question_id' => $question_id,
                        'text_feedback' => null,
                        'type' => 'q',
                        'grader_id' => null,
                        'text_feedback_editor' => null,
                        'date_graded' => null
                    ]);
                    $individual_submission_file->save();
                    $count++;
                    echo $user_id . ' ' . $question_id . "\r\n";
                }
            }
        }
        DB::commit();
    }
}
