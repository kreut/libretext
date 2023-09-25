<?php

namespace App\Console\Commands\LMS;

use App\Custom\LTIDatabase;
use App\Exceptions\Handler;
use App\LtiGradePassback;
use App\LtiLaunch;
use App\Score;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Overrides\IMSGlobal\LTI;

class passbackManualPendingScores extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'passback:manualPendingScores';

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
     * @param LtiGradePassback $ltiGradePassback
     * @return int
     * @throws Exception
     */
    public function handle(LtiGradePassback $ltiGradePassback): int
    {
        try {
            $passback_by_assignments = DB::table('passback_by_assignments')
                ->where('status', 'manual_pending')
                ->get();
            foreach ($passback_by_assignments as $passback_by_assignment) {
                DB::table('passback_by_assignments')
                    ->where('id', $passback_by_assignment->id)
                    ->where('status', 'manual_pending')
                    ->update(['status' => 'processing']);
                $scores = Score::where('assignment_id', $passback_by_assignment->assignment_id)->get();
                $scores_by_user_id = [];
                foreach ($scores as $score) {
                    $scores_by_user_id[$score->user_id] = $score->score;
                }

                $lti_launches = LtiLaunch::where('assignment_id', $passback_by_assignment->assignment_id)->get();
                echo count($lti_launches) . " launches";
                foreach ($lti_launches as $lti_launch) {
                    if (isset($scores_by_user_id[$lti_launch->user_id])) {
                        if (!in_array(app()->environment(), ['testing', 'local'])) {
                            $ltiGradePassback->passBackByUserIdAndAssignmentId($scores_by_user_id[$lti_launch->user_id], $lti_launch);
                        } else {
                            echo $scores_by_user_id[$lti_launch->user_id] . ' ';
                            $launch = LTI\LTI_Message_Launch::from_cache($lti_launch->launch_id, new LTIDatabase());
                            echo $launch->get_launch_data()['given_name'] . ' ' . $launch->get_launch_data()['family_name'] . ' ' . $launch->get_launch_data()['email'] . "\r\n";
                        }
                    }
                }
                DB::table('passback_by_assignments')
                    ->where('id', $passback_by_assignment->id)
                    ->where('status', 'processing')
                    ->update(['status' => 'completed']);
            }
        } catch (Exception $e) {
            echo $e->getMessage();
            $h = new Handler(app());
            $h->report($e);

            return 1;

        }

        return 0;
    }
}
