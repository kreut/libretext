<?php

namespace App\Console\Commands;

use App\Assignment;
use App\GraderNotification;
use App\Section;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use App\Exceptions\Handler;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class graderNotificationsForLateSubmissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notify:gradersForLateSubmissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify graders for late submissions';

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
    public function handle(GraderNotification $graderNotification, Assignment $assignment)
    {
        try {
            $grader_notifications = DB::table('grader_notifications')
                ->where('for_late_submissions', 1)
                ->get();


            if (!$grader_notifications) {
                exit;
            }

            $course_ids = [];
            foreach ($grader_notifications as $grader_notification) {
                $course_ids[] = '"' . $grader_notification->course_id . '"';
            }
            $course_ids = implode(', ', $course_ids);
            if (!$course_ids){
                exit;
            }
            $last_24_hours = Carbon::now()->subDay()->format('Y-m-d H:i:s');
            $where = "date_graded IS NULL
                        AND date_submitted > due
                        AND TYPE != 'a'
                        AND date_submitted >= '$last_24_hours'
                        AND courses.id IN ($course_ids)";

            $sql = $graderNotification->submissionSQL($where);
            $ungraded_submissions = DB::select(DB::raw($sql));

            if (!$ungraded_submissions) {
                exit;
            }

            $process_ungraded_submissions = $graderNotification->processUngradedSubmissions($ungraded_submissions, $assignment);
            $graders_by_id = $process_ungraded_submissions['graders_by_id'];
            $formatted_ungraded_submissions_by_grader = $process_ungraded_submissions['formatted_ungraded_submissions_by_grader'];

            foreach ($graders_by_id as $grader_id => $grader) {
                $graderNotification->sendReminder($grader, $formatted_ungraded_submissions_by_grader[$grader_id],'emails.late_submission_notification');

            }

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
        }
    }
}
