<?php

namespace App\Console\Commands\Users\id_5;

use App\Score;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class delmarFix4_26_5_53am extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'delmar:Fix4_26_5_53am';

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
        /*If the score on assignment 318 is greater than than assignment 360,
        can we override the total score on assignment 360 to be that of assignment 318?*/
        DB::beginTransaction();
        $assignment_id_1 = 318;
        $assignment_id_2 = 360;

        $this->updateScores($assignment_id_1, $assignment_id_2);
        /**
         * If the score on assignment 333 is greater than than assignment 542,
         * can we override the total score on assignment 542 to be that of assignment 333?**/

        $assignment_id_1 = 333;
        $assignment_id_2 = 542;
        $this->updateScores($assignment_id_1, $assignment_id_2);

        /*If the score on assignment 347 is greater than than assignment 518,
        can we override the total score on assignment 518 to be that of assignment 347?
        $assignment_id_1 = 347;
        $assignment_id_2 = 518;*/
        $this->updateScores($assignment_id_1, $assignment_id_2);
        DB::commit();
    }

    function updateScores($assignment_id_1, $assignment_id_2)
    {
        $score = new Score();
        $scores_1_by_id = [];
        $scores_1 = $score->where('assignment_id', $assignment_id_1)->get();
        foreach ($scores_1 as $value) {
            $scores_1_by_id[$value->user_id] = $value->score;
        }

        $scores_2 = $score->where('assignment_id', $assignment_id_2)->get();
        foreach ($scores_2 as $value) {
            $scores_2_by_id[$value->user_id] = $value->score;
        }
        foreach ($scores_1_by_id as $user_id => $score_1) {
            if (isset($scores_2_by_id[$user_id])) {
                $score_2 = $scores_2_by_id[$user_id];
                if ($score_1 > $score_2) {
                    $score = new Score();
                    $old_score = $score->where('assignment_id', $assignment_id_2)
                        ->where('user_id', $user_id)
                        ->first();
                    $old_score->score = $score_1;
                    $old_score->save();
                }
            }
        }
    }
}
