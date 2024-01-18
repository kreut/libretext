<?php

namespace App\Console\Commands\LMS;

use App\CanvasMaxAttemptsError;
use App\Exceptions\Handler;
use App\LtiGradePassback;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Telegram\Bot\Laravel\Facades\Telegram;

class retryFailedGradePassbacks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'retry:FailedGradePassbacks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Passback grades where there were failures';

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
     * @param LtiGradePassback $ltiGradePassback
     * @return int
     * @throws Exception
     */
    public function handle(LtiGradePassback $ltiGradePassback): int
    {
        try {
            //try one more time...
            $failed_lti_grade_passbacks_and_launch_infos = $this->_failedLtiGradePassbacksAndLaunchInfos();
            foreach ($failed_lti_grade_passbacks_and_launch_infos as $failed_lti_grade_passbacks_and_launch_info) {
                //    if (!app()->environment('local')) {
                $ltiGradePassback->passBackByUserIdAndAssignmentId($failed_lti_grade_passbacks_and_launch_info->score, $failed_lti_grade_passbacks_and_launch_info);
                //     }
            }
            $failed_lti_grade_passbacks_and_launch_infos = $this->_failedLtiGradePassbacksAndLaunchInfos();

            foreach ($failed_lti_grade_passbacks_and_launch_infos as $key => $failed_lti_passback) {
                if ($failed_lti_passback->user_id === 3847 && $failed_lti_passback->assignment_id === 7098) {
                    unset($failed_lti_grade_passbacks_and_launch_infos[$key]);
                }
                if (strpos($failed_lti_passback->message, 'The maximum number of allowed attempts has been reached for this submission') !== false) {
                    CanvasMaxAttemptsError::firstOrCreate(['assignment_id' => $failed_lti_passback->assignment_id]);
                    unset($failed_lti_grade_passbacks_and_launch_infos[$key]);
                }

                if (strpos($failed_lti_passback->message, 'User not found in course or is not a student') !== false) {
                    unset($failed_lti_grade_passbacks_and_launch_infos[$key]);
                }

            }
            if (count($failed_lti_grade_passbacks_and_launch_infos)) {
                $verb = count($failed_lti_grade_passbacks_and_launch_infos) === 1 ? "was" : "were";
                $message = count($failed_lti_grade_passbacks_and_launch_infos) . " grade passback $verb not successful.";

                Telegram::sendMessage([
                    'chat_id' => config('myconfig.telegram_channel_id'),
                    'parse_mode' => 'HTML',
                    'text' => $message
                ]);
            }
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);

            return 1;
        }
        echo "No errors.";
        return 0;
    }

    /**
     * @return Collection
     */
    private
    function _failedLtiGradePassbacksAndLaunchInfos(): Collection
    {
        return DB::table('lti_grade_passbacks')
            ->join('lti_launches', 'lti_grade_passbacks.launch_id', '=', 'lti_launches.launch_id')
            ->where('status', '<>', 'success')
            ->where('message','NOT LIKE','%This course has concluded. AGS requests will no longer be accepted for this course.%')
            ->where('message','NOT LIKE','%User not found in course or is not a student%')
            ->where('message','NOT LIKE',"%Invalid access token field/s: the 'aud' is invalid%")
            ->where('message','NOT LIKE',"%Context is deleted or not found%")
            ->where('lti_grade_passbacks.created_at', '<=', Carbon::now()->subMinutes(2)->toDateTimeString())
            ->get();
    }
}
