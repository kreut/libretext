<?php

namespace App\Console\Commands;

use App\Exceptions\Handler;
use App\LtiGradePassback;
use Exception;
use Illuminate\Console\Command;
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
            $failed_lti_grade_passbacks_and_launch_infos = DB::table('lti_grade_passbacks')
                ->join('lti_launches', 'lti_grade_passbacks.launch_id', '=', 'lti_launches.launch_id')
                ->where('success', '<>', 1)
                ->get();
            if (count($failed_lti_grade_passbacks_and_launch_infos)) {
                $verb = count($failed_lti_grade_passbacks_and_launch_infos) === 1 ? "was" : "were";
                $message = count($failed_lti_grade_passbacks_and_launch_infos) . " $verb not successful.   ";
                foreach ($failed_lti_grade_passbacks_and_launch_infos as $failed_lti_grade_passbacks_and_launch_info) {
                    $ltiGradePassback->passBackByUserIdAndAssignmentId($failed_lti_grade_passbacks_and_launch_info->score, $failed_lti_grade_passbacks_and_launch_info);
                }

                $num_not_successful = DB::table('lti_grade_passbacks')
                    ->where('success', '<>', 1)
                    ->count();

                $message .= $num_not_successful
                    ? "There are still $num_not_successful failed grade passbacks."
                    : "There are no more failed grade passbacks.";

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
        return 0;
    }
}
