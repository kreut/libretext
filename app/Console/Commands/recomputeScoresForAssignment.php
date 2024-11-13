<?php

namespace App\Console\Commands;

use App\Ltigrade_passback;
use App\LtiGradePassback;
use App\LtiLaunch;
use App\Score;
use App\Submission;
use App\User;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class recomputeScoresForAssignment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recompute:ScoresForAssignment {assignment_id}';

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
            //just works for a straight up computation of an assignment that is auto-graded
            $assignment_id = $this->argument('assignment_id');
            $user_scores = Submission::where('assignment_id', $assignment_id)
                ->select('user_id', DB::raw('SUM(score) as total_score'))
                ->groupBy('user_id')
                ->get();
            $ltiGradePassback = new LtiGradePassback();
            $ltiLaunch = new LtiLaunch();
            foreach ($user_scores as $user_score) {
                $assignment_score = Score::where('user_id', $user_score->user_id)
                    ->where('assignment_id', $assignment_id)
                    ->first();
                $lti_grade_passback = LtiGradePassback::where('user_id', $user_score->user_id)
                    ->where('assignment_id', $assignment_id)
                    ->first();
                if ((int)$user_score->total_score !== (int)$lti_grade_passback->score
                    || (int)$user_score->total_score !== (int)$assignment_score->score) {
                    $user = User::find($user_score->user_id);

                    echo($user_score->user_id . ' ' . $user->first_name . ' ' . ' ' . $user->last_name . ' ' . $user_score->total_score . ' ' . $lti_grade_passback->score . "\r\n");
                    $num_updated = $assignment_score->update(['score' => $user_score->total_score]);
                    if ($num_updated > 1) {
                        throw new Exception ("only 1 score should be updated.");
                    }
                    $num_updated = $lti_grade_passback->update(['score' => $user_score->total_score]);
                    if ($num_updated > 1) {
                        throw new Exception ("only 1 lti grade passback should be updated.");
                    }
                    $lti_launch = $ltiLaunch->where('launch_id', $lti_grade_passback->launch_id)->first();
                    if (!$lti_launch) {
                        throw new Exception ("$lti_grade_passback->launch_id does not exist.");
                    }
                    $ltiGradePassback->passBackByUserIdAndAssignmentId($lti_grade_passback->score, $lti_launch);
                }

            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            echo $e->getMessage();
        }
        return 0;
    }
}
