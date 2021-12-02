<?php

namespace App\Console\Commands\Notifications;

use App\Exceptions\Handler;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class notifyBetaCourseApprovals extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notify:BetaCourseApprovals';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tell instructors when they need to approve assignments';

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
            $yesterday = Carbon::now()->subDay()->format('Y-m-d H:i:s');
            $pending_approvals = DB::table('beta_course_approvals')
                ->join('assignments', 'beta_course_approvals.beta_assignment_id', '=', 'assignments.id')
                ->join('courses', 'assignments.course_id', '=', 'courses.id')
                ->join('users', 'courses.user_id', '=', 'users.id')
                ->select('courses.id', 'courses.name AS course_name',
                    'assignments.id as assignment_id',
                    'assignments.name AS assignment_name',
                    DB::raw('count(*) AS total_pending'),
                    'users.id AS user_id',
                    'users.email',
                    'users.first_name'
                )
                ->where('courses.beta_approval_notifications', 1)
                ->where('beta_course_approvals.created_at', '>', $yesterday)
                ->groupBy('assignments.id')
                ->get();
            $pending_approvals_by_user_id = [];
            foreach ($pending_approvals as $pending_approval) {
                if (!isset($pending_approval_by_user_id[$pending_approval->user_id])) {
                    $pending_approvals_by_user_id[$pending_approval->user_id] = [];
                    $pending_approvals_by_user_id[$pending_approval->user_id]['first_name'] = $pending_approval->first_name;
                    $pending_approvals_by_user_id[$pending_approval->user_id]['email'] = $pending_approval->email;
                    $pending_approvals_by_user_id[$pending_approval->user_id]['pending_approvals'] = '';
                }
                $app_url = config('app.url');
                $pending_approvals_by_user_id[$pending_approval->user_id]['pending_approvals'] .= "<li>$pending_approval->course_name: <a href='$app_url/instructors/assignments/$pending_approval->assignment_id/information/questions'>$pending_approval->assignment_name</a> has $pending_approval->total_pending pending approvals</li>";
            }

            foreach ($pending_approvals_by_user_id as $pending_approval) {
                $beauty_mail = app()->make(\Snowfire\Beautymail\Beautymail::class);
                $to_email = $pending_approval['email'];
                $email_info = ['pending_approvals' => $pending_approval['pending_approvals'],
                    'first_name' => $pending_approval['first_name']
                ];
                $beauty_mail->send('emails.pending_beta_course_approvals', $email_info, function ($message)
                use ($to_email) {
                    $message
                        ->from('adapt@noreply.libretexts.org', 'ADAPT')
                        ->to($to_email)
                        ->subject("Pending Beta Course Approvals");
                });
            }
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
          return 1;
        }
        return 0;
    }
}
