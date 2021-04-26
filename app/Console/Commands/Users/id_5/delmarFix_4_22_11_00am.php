<?php

namespace App\Console\Commands\Users\id_5;

use App\Score;
use App\Submission;
use App\SubmissionFile;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class delmarFix_4_22_11_00am extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'delmar:Fix_4_22_11_00am';

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
        DB::beginTransaction();
        $submissionFile = new SubmissionFile();
        $submissions = $submissionFile->where('assignment_id', 392)->where('question_id', 98766)->get();
        foreach ($submissions as $submission) {
            $current_submission_score = $submission->score;
            $submission->score = 5;
            $adjustment = 5 - $current_submission_score;
            $submission->save();

            $score = new Score();
            $current = $score->where('assignment_id', 392)
                ->where('user_id', $submission->user_id)
                ->first();
            $current->score = $current->score + $adjustment;
            $current->save();
        }
        DB::commit();
    }
}
