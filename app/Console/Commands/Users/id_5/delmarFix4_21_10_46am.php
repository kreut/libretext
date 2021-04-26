<?php

namespace App\Console\Commands\Users\id_5;

use App\Score;
use App\Submission;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class delmarFix4_21_10_46am extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Delmar:Fix4_21_10_40am';

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
        $submissions = $submission->where('assignment_id', 392)->where('question_id', 98881)->get();

        foreach ($submissions as $submission) {
            $submission_arr = json_decode($submission->submission, true);
            $give_credit = (int)$submission_arr['score']['result'] === 0;
            if ($give_credit) {
                $submission->score = 2;
                $submission->save();
                $score = new Score();
                $current = $score->where('assignment_id', 392)->where('user_id', $submission->user_id)->first();
                $current->score = $current->score + 2;
                $current->save();
            }

            DB::commit();
        }
    }
}
