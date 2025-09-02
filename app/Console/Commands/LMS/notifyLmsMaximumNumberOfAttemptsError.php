<?php

namespace App\Console\Commands\LMS;

use App\Exceptions\Handler;
use App\Helpers\Helper;
use App\LtiGradePassback;
use App\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Snowfire\Beautymail\Beautymail;

class notifyLmsMaximumNumberOfAttemptsError extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notify:LmsMaximumNumberOfAttemptsError';

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
    public function handle()
    {
        try {
            $fiveMinutesAgo = Carbon::now()->subMinutes(5);
            $sent_notification_assignment_ids = DB::table('maximum_number_of_allowed_attempts_notifications')
                ->select('assignment_id')
                ->get()
                ->pluck('assignment_id')
                ->toArray();
            $maximum_number_of_allowed_attempts = LtiGradePassback::where('updated_at', '>', $fiveMinutesAgo)
                ->where('status', 'error')
                ->where('message', 'LIKE', '%maximum number of allowed attempts%')
                ->get();
            $assignment_ids_needing_notifications = [];
            foreach ($maximum_number_of_allowed_attempts as $value) {
                if (!in_array($value->assignment_id, $sent_notification_assignment_ids)) {
                    $assignment_ids_needing_notifications[] = $value->assignment_id;
                }
            }
            $assignment_ids_needing_notifications = array_unique($assignment_ids_needing_notifications);
            $beauty_mail = app()->make(Beautymail::class);
            if ($assignment_ids_needing_notifications) {

                $instructor_infos = DB::table('assignments')
                    ->join('courses', 'assignments.course_id', '=', 'courses.id')
                    ->join('users', 'courses.user_id', '=', 'users.id')
                    ->select('courses.name AS course_name',
                        'assignments.id AS assignment_id',
                        'assignments.name AS assignment_name',
                        'users.email')
                    ->whereIn('assignments.id', $assignment_ids_needing_notifications)
                    ->get();
                foreach ($instructor_infos as $instructor_info) {
                    $email_info = ['course_name' => $instructor_info->course_name,
                        'assignment_name' => $instructor_info->assignment_name,
                        'url' => Helper::schemaAndHost() . "instructors/assignments/$instructor_info->assignment_id/information/resend-grades-to-lms"
                    ];
                    $to_email = $instructor_info->email;
                    try {
                        $id = '';
                        $id = DB::table('maximum_number_of_allowed_attempts_notifications')
                            ->insertGetId(['assignment_id' => $instructor_info->assignment_id,
                                'created_at' => now(),
                                'updated_at' => now()]);
                        $beauty_mail->send('emails.maximum_number_of_allowed_attempts_notifications', $email_info, function ($message)
                        use ($to_email) {
                            $message
                                ->from('adapt@noreply.libretexts.org', 'ADAPT')
                                ->to($to_email)
                                ->subject('Incorrect Canvas Setting');
                        });
                        DB::table('maximum_number_of_allowed_attempts_notifications')
                            ->where('id', $id)
                            ->update(['status' => 'success', 'updated_at'=>now()]);
                    } catch (Exception $e) {
                        if (!$id) {
                            throw new Exception("Could not get ID in the notifyLmsMaximumNUmberOfAttemptsError script");
                        }
                        DB::table('maximum_number_of_allowed_attempts_notifications')
                            ->where('id', $id)
                            ->update(['status' => 'error', 'message' => $e->getMessage()]);
                    }
                }
            }
        } catch (Exception $e) {
            dd($e->getMessage());
            $h = new Handler(app());
            $h->report($e);
            return 1;
        }
        return 0;
    }
}
