<?php

namespace App\Console\Commands\Users\id_5;

use App\Score;
use App\Submission;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class delmarFix4_19_10_20am extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Delmar:Fix4_19_10_20am';

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
        $score = new Score();
        $score->where('assignment_id', 316)->update(['score' => 20]);
        $score = new Score();
        $score->where('assignment_id', 390)->update(['score' => 20]);

        $submission = new Submission();
        //On ADAPT ID: 392-98875 can I make all true submissions to "zero score"
        $num_b_submissions = 0;
        $submissions = $submission->where('assignment_id', 392)->where('question_id', 98880)->get();

        foreach ($submissions as $submission) {
            $submission_arr = json_decode($submission->submission, true);
            $B_submission = $submission_arr['score']['answers']['AnSwEr0001']['original_student_ans'] === 'B';
            $num_b_submissions = (int) $B_submission + $num_b_submissions ;
            $submission->score = $B_submission ? 2 : 0;
            $submission->save();
            $score = new Score();
            $current = $score->where('assignment_id', 392)->where('user_id', $submission->user_id)->first();
            $adjustment = $B_submission ? 2 : -2;
            $current->score = $current->score + $adjustment;
            $current->save();
        }

        DB::commit();
    }
}
