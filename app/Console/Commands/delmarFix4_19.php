<?php

namespace App\Console\Commands;

use App\Score;
use App\Submission;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class delmarFix4_19 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Delmar:Fix';

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
        //give score of 100 to everyone who submitted to 319 and 320
       /* $submission = new Submission();
        $responses = '';
       $submissions = $submission->where('assignment_id', 392)->where('question_id', 98762)->get();
        foreach ($submissions as $submission) {
            $submission_arr = json_decode($submission->submission, true);
            $result = $submission_arr['score']['answers']['AnSwEr0001']['original_student_ans'];
            $responses .= $result . ', ';

        }
        dd($responses);*/
        DB::beginTransaction();
        $score = new Score();
        $score->where('assignment_id', 319)->update(['score' => 100]);
        $score = new Score();
        $score->where('assignment_id', 320)->update(['score' => 160]);

        $submission = new Submission();
        //On Adapt ID: 392-98875 can I make all true submissions to "zero score"
        $submissions = $submission->where('assignment_id', 392)->where('question_id', 98875)->get();
        foreach ($submissions as $submission) {
            $submission_arr = json_decode($submission->submission, true);
            $true_submission = $submission_arr['score']['answers']['AnSwEr0001']['original_student_ans'] === 'A';
            $submission->score = $true_submission ? 0 : 2;
            $submission->save();
            $score = new Score();
            $current = $score->where('assignment_id', 392)->where('user_id', $submission->user_id)->first();
            $adjustment = $true_submission ? -2 : 2;
            $current->score = $current->score + $adjustment;
            $current->save();

        }
        /*The students that submitted A for 392-98765 should get it correct and
       the rest should not.*/
        $submissions = $submission->where('assignment_id', 392)->where('question_id', 98765)->get();
        foreach ($submissions as $submission) {
            $submission_arr = json_decode($submission->submission, true);
            $correct_submission = $submission_arr['score']['answers']['AnSwEr0001']['original_student_ans'] === 'A';
            $submission->score = $correct_submission ? 4 : 0;
            $submission->save();
            $score = new Score();
            $current = $score->where('assignment_id', 392)->where('user_id', $submission->user_id)->first();
            $adjustment = $correct_submission ? 4 : -4;
            $current->score = $current->score + $adjustment;
            $current->save();
        }
//Another one: 392-98764
        $submissions = $submission->where('assignment_id', 392)->where('question_id', 98764)->get();
        foreach ($submissions as $submission) {
            $submission_arr = json_decode($submission->submission, true);
            $correct_submission = $submission_arr['score']['answers']['AnSwEr0001']['original_student_ans'] === 'C';
            $submission->score = $correct_submission ? 4 : 0;
            $submission->save();
            $score = new Score();
            $current = $score->where('assignment_id', 392)->where('user_id', $submission->user_id)->first();
            $adjustment = $correct_submission ? 4 : -4;
            $current->score = $current->score + $adjustment;
            $current->save();
        }

        DB::commit();

        /*I want every Chem 2C student to get 100% on assignments 319 and 320.
         I presume a simple override of the final score is needed instead of playing around with each assessment score.

        and all "false submission" into full credit of 2 points?
        /*The students that submitted A for 392-98765 should get it correct and
        the rest should not.*/
    }
}
