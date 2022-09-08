<?php

namespace App\Console\Commands\LMS;

use App\Exceptions\Handler;
use App\LtiGradePassback;
use App\LtiLaunch;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class passbackByLtiGradePassbackId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'passback:byLtiGradePassbackId {id}';

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
     * @throws Exception
     */
    public function handle(LtiGradePassback $ltiGradePassback, LtiLaunch $ltiLaunch)
    {

        try {
            $lti_grade_passback_id = $this->argument('id');
            $lti_grade_passback = DB::table('lti_grade_passbacks')->where('id', $lti_grade_passback_id)->first();

            $lti_launch = $ltiLaunch->where('launch_id', $lti_grade_passback->launch_id)->first();
            if (!$lti_launch) {

                throw new Exception ("$lti_grade_passback->launch_id does not exist.");
            }

            $ltiGradePassback->passBackByUserIdAndAssignmentId($lti_grade_passback->score, $ltiLaunch);


        } catch (Exception $e) {
            echo $e->getMessage();
            $h = new Handler(app());
            $h->report($e);

            return 1;
        }
        echo "No errors.";
        return 0;
    }
}
