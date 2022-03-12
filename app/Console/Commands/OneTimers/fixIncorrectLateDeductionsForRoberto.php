<?php

namespace App\Console\Commands\OneTimers;

use App\Score;
use App\Submission;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class fixIncorrectLateDeductionsForRoberto extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:incorrectLateDeductionsForRoberto';

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
            $this->_updateScores(3665, 25, 50);
            $this->_updateScores(3666, 15, 30);
            DB::commit();
            echo "success";
        } catch (Exception $e) {
            DB::rollback();
            echo $e->getMessage();
        }
    }

    private function _updateScores($assignment_id,
                                   $old_assignment_score,
                                   $new_assignment_score)
    {
        $user_ids = DB::table('scores')
            ->where('assignment_id', $assignment_id)
            ->select('user_id')
            ->where('score', $old_assignment_score)
            ->get()
            ->pluck('user_id')
            ->toArray();
        $num_submissions = $num_scores = 0;
        foreach ($user_ids as $user_id) {
            $num_submissions += Submission::where('assignment_id', $assignment_id)
                ->where('user_id', $user_id)
                ->where('score', 5)
                ->update(['score' => 10]);
            $num_scores += Score::where('assignment_id', $assignment_id)
                ->where('user_id', $user_id)
                ->update(['score' => $new_assignment_score]);

        }
        echo "$assignment_id:  Number of submissions: $num_submissions. Number of scores: $num_scores\r\n";
    }
}
