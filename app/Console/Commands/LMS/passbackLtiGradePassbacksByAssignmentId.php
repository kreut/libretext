<?php

namespace App\Console\Commands\LMS;

use App\Exceptions\Handler;
use App\LtiGradePassback;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class passbackLtiGradePassbacksByAssignmentId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'passback:ltiGradePassbacksByAssignmentId {id} {update_assignment_score}';

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
     * @return int
     * @throws Exception
     */
    public function handle()
    {
        try {
            $update_assignment_score = $this->argument('update_assignment_score');
            $assignment_id = $this->argument('id');
            $lti_grade_passbacks = LtiGradePassback::where('assignment_id', $assignment_id)
                ->get();
            if ($update_assignment_score) {
                $scores = DB::table('scores')->where('assignment_id', $assignment_id)->get();
                foreach ($scores as $score) {
                    $scores_by_user_id[$score->user_id] = $score->score;
                }
            }
            foreach ($lti_grade_passbacks as $lti_grade_passback) {
                if ($update_assignment_score && isset($scores_by_user_id[$lti_grade_passback->user_id])) {
                    $lti_grade_passback->score = $scores_by_user_id[$lti_grade_passback->user_id];
                    $lti_grade_passback->save();
                }
                $this->call('passback:byLtiGradePassbackId', ['id' => $lti_grade_passback->id]);
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
