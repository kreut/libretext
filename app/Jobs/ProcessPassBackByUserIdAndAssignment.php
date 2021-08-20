<?php

namespace App\Jobs;

use App\Assignment;
use App\LtiGradePassback;
use App\LtiLaunch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessPassBackByUserIdAndAssignment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Assignment
     */
    private $assignment;
    /**
     * @var int
     */
    private $user_id;
    private $score_to_pass_back;
    /**
     * @var LtiLaunch
     */
    private $ltiLaunch;
    private $score;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($score, $ltiLaunch)

    {

        $this->score = $score;
        $this->ltiLaunch = $ltiLaunch;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $ltiGradePassback = new LtiGradePassback();
        $ltiGradePassback->passBackByUserIdAndAssignmentId($this->score, $this->ltiLaunch);
    }
}
