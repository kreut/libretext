<?php

namespace App\Console\Commands;

use App\GraderNotification;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use App\Exceptions\Handler;
use Illuminate\Support\Facades\DB;

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
    public function handle(GraderNotification $graderNotification)
    {
        try {
            $last_24_hours = Carbon::now()->subDay()->format('Y-m-d H:i:s');
            $where = 'date_graded IS NULL AND date_submitted > due AND TYPE = "q" AND date_submitted >= "' . $last_24_hours . '"';
            $sql = $graderNotification->submissionSQL($where);
            $late_submissions = DB::select(DB::raw($sql));

            $late_submissions_by_section_info = $graderNotification->submissionsBySection($late_submissions);
            $late_submissions_by_section =     $late_submissions_by_section_info['submissions_by_section'];
            $section_ids =  $late_submissions_by_section_info['section_ids'];
            $graders_by_id = $graderNotification->gradersInfo($section_ids);

            foreach ($graders_by_id as $grader) {
                $late_submissions_by_grader = [];
                foreach ($grader['section_ids'] as $section_id) {

                    if (in_array($section_id, array_keys($late_submissions_by_section))) {
                        $late_submissions_by_grader[] = $late_submissions_by_section[$section_id];

                        /**  +"assignment_id": 21
                         * +"user_id": 55
                         * +"question_id": 97912
                         * +"id": 21
                         * +"assignment_name": "Weekly HW #4"
                         * +"section_id": 2
                         * +"section_name": "Main"
                         **/
                    }
                    //send out the email
                    $beauty_mail = app()->make(\Snowfire\Beautymail\Beautymail::class);
                    $to_email = $grader['email'];

                    $grading_info = ['late_submissions_by_grader' => $late_submissions_by_grader,
                        'first_name' => $grader['first_name']
                    ];

                    $beauty_mail->send('emails.late_submission_notification', $grading_info, function ($message)
                    use ($to_email) {
                        $message
                            ->from('adapt@libretexts.org')
                            ->to($to_email)
                            ->subject('Late Submission Needs Grading');
                    });

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
