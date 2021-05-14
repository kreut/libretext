<?php

namespace App\Console\Commands;

use App\Assignment;
use App\Course;
use App\Exceptions\Handler;
use App\GraderNotification;
use App\Section;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class graderNotificationsReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notify:gradersReminders';

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
     * @param GraderNotification $graderNotification
     * @param Assignment $assignment
     * @throws Exception
     */
    public function handle(GraderNotification $graderNotification,
                           Assignment $assignment)
    {
        try {

            $day = Carbon::now()->dayOfWeek;


            $num_reminders_per_week = [];

            switch ($day) {
                case(0):
                    $num_reminders_per_week = [1, 2, 3, 7];
                    break;
                case(2):
                case(4):
                    $num_reminders_per_week = [3, 7];
                    break;
                case(3):
                    $num_reminders_per_week = [2, 7];
                    break;
                case(1):
                case(5):
                case(6):
                    $num_reminders_per_week = [7];
                    break;
            }
            $grader_notifications = DB::table('grader_notifications')
                ->whereIn('num_reminders_per_week', $num_reminders_per_week)
                ->get();


            if (!$grader_notifications) {
                exit;
            }
            $course_ids = [];
            foreach ($grader_notifications as $grader_notification) {
                $course_ids[] = $grader_notification->course_id;
            }

            $copy_to_instructors_by_course_id = $this->getCopyToInstructorsByCourseId($course_ids);
            $copy_to_head_graders_by_course_id = $this->getCopyToHeadGradersByCourseId($course_ids);

            $copy_to_course_ids = [];
            foreach ($copy_to_instructors_by_course_id as $course_id => $value) {
                $copy_to_course_ids[] = $course_id;
            }
            foreach ($copy_to_head_graders_by_course_id as $course_id => $value) {
                $copy_to_course_ids[] = $course_id;
            }
            $copy_to_course_ids = array_unique($copy_to_course_ids);

            $course_ids = [];
            foreach ($grader_notifications as $grader_notification) {
                $course_ids[] = '"' . $grader_notification->course_id . '"';
            }
            $course_ids = implode(', ', $course_ids);


            $yesterday = Carbon::now()->subDay()->format('Y-m-d H:i:s');
            $where = "date_graded IS NULL
                      AND due < '$yesterday'
                      AND TYPE != 'a'
                      AND courses.id IN ($course_ids)";


            $sql = $graderNotification->submissionSQL($where);

            $ungraded_submissions = DB::select(DB::raw($sql));
            $process_ungraded_submissions = $graderNotification->processUngradedSubmissions($ungraded_submissions, $assignment);
            $graders_by_id = $process_ungraded_submissions['graders_by_id'];
            $formatted_ungraded_submissions_by_grader = $process_ungraded_submissions['formatted_ungraded_submissions_by_grader'];

            //send the emails to the graders
            foreach ($graders_by_id as $grader_id => $grader) {
                $graderNotification->sendReminder($grader, $formatted_ungraded_submissions_by_grader[$grader_id],'emails.grader_reminder');

            }

            //do for the instructors and head graders
            $formatted_ungraded_submissions_by_course = [];
            foreach ($copy_to_course_ids as $course_id) {
                $formatted_ungraded_submissions_by_course[$course_id] = '';
                foreach ($formatted_ungraded_submissions_by_grader as $grader_id => $formatted_ungraded_submission) {
                    $formatted_ungraded_submissions_by_course[$course_id] .= "<p style='margin-top:50px'><strong>{$graders_by_id[$grader_id]['first_name']} {$graders_by_id[$grader_id]['last_name']}</strong></p>";
                    $formatted_ungraded_submissions_by_course[$course_id] .= $formatted_ungraded_submission;
                }
            }

            foreach ($copy_to_instructors_by_course_id as $course_id => $copy_to_instructor) {
                $this->sendCopyTo($copy_to_instructor, $formatted_ungraded_submissions_by_course[$course_id]);
            }

            foreach ($copy_to_head_graders_by_course_id as $course_id => $copy_to_grader) {
                $this->sendCopyTo($copy_to_grader, $formatted_ungraded_submissions_by_course[$course_id]);
            }


        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
        }
    }



    public function sendCopyTo(array $copy_to, string $formatted_ungraded_submissions_by_course)
    {

        $beauty_mail = app()->make(\Snowfire\Beautymail\Beautymail::class);
        $to_email = $copy_to['email'];

        $grading_info = ['formatted_ungraded_submissions_by_course' => $formatted_ungraded_submissions_by_course,
            'first_name' => $copy_to['first_name']
        ];

        $beauty_mail->send('emails.summary_of_grader_reminders', $grading_info, function ($message)
        use ($to_email) {
            $message
                ->from('adapt@libretexts.org')
                ->to($to_email)
                ->subject("Summary of Ungraded Assignments");
        });
    }

    /**
     * @param $course_ids
     * @return array
     */
    public function getCopyToInstructorsByCourseId($course_ids): array
    {

        $copy_to_instructors = DB::table('courses')
            ->join('users', 'courses.user_id', '=', 'users.id')
            ->join('grader_notifications', 'courses.id', '=', 'grader_notifications.course_id')
            ->whereIn('courses.id', $course_ids)
            ->where('copy_grading_reminder_to_instructor', 1)
            ->select('courses.id AS course_id', 'users.first_name', 'users.email')
            ->get();
        $copy_to_instructors_by_course_id = [];
        foreach ($copy_to_instructors as $instructor) {
            $copy_to_instructors_by_course_id[$instructor->course_id] = ['first_name' => $instructor->first_name,
                'email' => $instructor->email];
        }
        return $copy_to_instructors_by_course_id;
    }

    /**
     * @param $course_ids
     * @return array
     */
    public function getCopyToHeadGradersByCourseId($course_ids): array
    {
        $copy_to_head_graders = DB::table('courses')
            ->join('head_graders', 'courses.id', '=', 'head_graders.course_id')
            ->join('grader_notifications', 'courses.id', '=', 'grader_notifications.course_id')
            ->join('users', 'head_graders.user_id', '=', 'users.id')
            ->whereIn('courses.id', $course_ids)
            ->where('copy_grading_reminder_to_head_grader', 1)
            ->select('courses.id AS course_id', 'users.first_name', 'users.email')
            ->get();

        $copy_to_head_graders_by_course_id = [];
        foreach ($copy_to_head_graders as $head_grader) {
            $copy_to_head_graders_by_course_id[$head_grader->course_id] = ['first_name' => $head_grader->first_name, 'email' => $head_grader->email];
        }
        return $copy_to_head_graders_by_course_id;
    }

}
