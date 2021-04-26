<?php

namespace App\Console\Commands\Users\id_5;

use App\Score;
use App\Submission;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class delmarFix_4_21_11_13am extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'delmar:Fix_4_21_11_13am';

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
        $submission = new Submission();
        $submissions = $submission->where('assignment_id', 392)->where('question_id', 98764)->get();
        foreach ($submissions as $submission) {
            $submission_arr = json_decode($submission->submission, true);
            $adjustment = 0;
            $add_4 = $submission_arr['score']['answers']['AnSwEr0001']['original_student_ans'] === 'A';
            if ($add_4) {
                $submission->score = 4;
                $adjustment = 4;
                $submission->save();
            }
            $subtract_4 = $submission_arr['score']['answers']['AnSwEr0001']['original_student_ans'] === 'C';
            if ($subtract_4) {
                $submission->score = 0;
                $adjustment = -4;
                $submission->save();
            }

            if ($add_4 || $subtract_4) {

                $score = new Score();
                $current = $score->where('assignment_id', 392)->where('user_id', $submission->user_id)->first();
                $current->score = $current->score + $adjustment;
                $current->save();
            }
        }
        DB::commit();
    }
}
