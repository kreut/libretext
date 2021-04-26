<?php

namespace App\Console\Commands\Users\id_5;

use App\Score;
use App\Submission;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class delmarFix4_21_11_02am extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'delmar:Fix_21_11_02am';

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
        //Another one: 392-98764
        //should not have deducted even more points if they answered other than C or A (assumed correct and actually correct)
       DB::beginTransaction();
       $submission = new Submission();
        $submissions = $submission->where('assignment_id', 392)->where('question_id', 98764)->get();
        foreach ($submissions as $submission) {
            $submission_arr = json_decode($submission->submission, true);
            $neither_c_nor_a = $submission_arr['score']['answers']['AnSwEr0001']['original_student_ans'] !== 'C'
                && $submission_arr['score']['answers']['AnSwEr0001']['original_student_ans'] !== 'A';
            if ($neither_c_nor_a) {
                $score = new Score();
                $current = $score->where('assignment_id', 392)->where('user_id', $submission->user_id)->first();

                $current->score = $current->score + 4;
                $current->save();
            }
        }
        DB::commit();

    }
}
