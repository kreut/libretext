<?php

namespace App\Console\Commands;

use App\Assignment;
use App\Course;
use App\SubmissionFile;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class dummyFilesubmission extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dummy:fileSubmission';

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
$assignment_id = 392;
        DB::beginTransaction();
        $combined_submission_files = SubmissionFile::where('assignment_id', $assignment_id)
                                        ->where('question_id', 99082)->get();

        $assignment_questions = DB::table('assignment_question')
            ->where('assignment_id', $assignment_id)
            ->where('open_ended_submission_type', 'file')
            ->where('question_id','<>',99082)
            ->get();
        foreach ($assignment_questions as $assignment_question) {
            $question_id = $assignment_question->question_id;

            foreach ($combined_submission_files as $combined_submission_file ){
                $user_id =  $combined_submission_file->user_id;
                $submission_file = DB::table('submission_files')
                    ->where('assignment_id', $assignment_id)
                    ->where('user_id', $user_id)
                    ->where('question_id', $question_id)
                    ->first();
                if (!$submission_file) {
                   $individual_submission_file =  $combined_submission_file->replicate()->fill([
                       'question_id' => $question_id,
                       'text_feedback' => null,
                       'grader_id' => null,
                       'text_feedback_editor' => null,
                       'date_graded' =>null
                   ]);
                    $individual_submission_file->save();
                }
            }
        }
        DB::commit();
    }

}
