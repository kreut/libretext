<?php

namespace App\Console\Commands;

use App\Exceptions\Handler;
use App\Grader;
use App\GraderNotification;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class graderNotificationsReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notify:gradersReminders {num_times_per_week}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remind them to grade if they have not already';


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
    public function handle(GraderNotification $graderNotification)
    {
        try {
            $num_times_per_week = $this->argument('num_times_per_week');

            $grader_notifications = DB::table('grader_notifications')
                ->where('num_times_per_week', $num_times_per_week)
                ->get();
            if (!$grader_notifications) {
                exit;
            }
            $course_ids = '';
            foreach ($grader_notifications as $grader_notification) {
                $course_ids[] = $grader_notification->course_id;
            }
            $course_ids = implode(', ', $course_ids);
            dd($course_ids);

            //Start: change to once, twice, then start testing
            $where = "date_graded IS NULL AND date_submitted > due AND TYPE = 'q' AND course_id IN ($course_ids)";


            $sql = $graderNotification->submissionSQL($where);
            $ungraded_submissions = DB::select(DB::raw($sql));

            $ungraded_submissions_by_section_info = $graderNotification->submissionsBySection($ungraded_submissions);
            $ungraded_submissions_by_section = $ungraded_submissions_by_section_info['submissions_by_section'];
            $section_ids = $ungraded_submissions_by_section_info['section_ids'];
            $course_ids = $ungraded_submissions_by_section_info['course_ids'];
            $graders_by_id = $graderNotification->gradersInfo($section_ids);

            foreach ($graders_by_id as $grader) {
                $ungraded_submissions_by_grader = [];
                foreach ($grader['section_ids'] as $section_id) {

                    if (in_array($section_id, array_keys($ungraded_submissions_by_section))) {
                        $ungraded_submissions_by_grader[] = $ungraded_submissions_by_section[$section_id];

                        /**  +"assignment_id": 21
                         * +"user_id": 55
                         * +"question_id": 97912
                         * +"id": 21
                         * +"assignment_name": "Weekly HW #4"
                         * +"section_id": 2
                         * +"section_name": "Main"
                         **/
                    }
                    if ($ungraded_submissions_by_grader) {
                        //send out the email
                        /**     $beauty_mail = app()->make(\Snowfire\Beautymail\Beautymail::class);
                         * $to_email = $grader['email'];
                         *
                         * $grading_info = ['ungraded_submissions_by_grader' => $ungraded_submissions_by_grader,
                         * 'first_name' => $grader['first_name']
                         * ];
                         *
                         * $beauty_mail->send('emails.ungraded_submission_notification', $grading_info, function ($message)
                         * use ($to_email) {
                         * $message
                         * ->from('adapt@libretexts.org')
                         * ->to($to_email)
                         * ->subject('Ungraded Submission Needs Grading');
                         * });
                         */
                    }
                }
            }
            //get the courses from the assignments
            //get the sections for the students
            //get the graders for the sections

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
        }
    }
}
