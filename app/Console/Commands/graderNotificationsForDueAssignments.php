<?php

namespace App\Console\Commands;


use App\Course;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class graderNotificationsForDueAssignments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notify:gradersForDueAssignments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tell graders when things are due';

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
    public function handle()
    {
        $now = Carbon::now()->format('Y-m-d H:i:00');
        $assign_to_timings = DB::table('assign_to_timings')
            ->join('assignments', 'assign_to_timings.assignment_id', '=', 'assignments.id')
            ->where('due', $now)
            ->select('assign_to_timings.id AS assign_to_timing_id',
                'assignments.id AS assignment_id',
                'assignments.course_id',
                'assignments.name AS assignment_name')
            ->get();
        foreach ($assign_to_timings as $assign_to_timing) {
            $course = Course::find($assign_to_timing->course_id);
            $graders = $course->graderInfo();
            foreach ($graders as $grader) {
                //get the sections that the grader has to grade
                $section_ids = array_keys($grader['sections']);
                $user_in_one_of_graders_sections = DB::table('assign_to_users')
                    ->join('enrollments', 'assign_to_users.user_id', '=', 'enrollments.user_id')
                    ->whereIn('section_id', $section_ids)
                    ->where('assign_to_timing_id', $assign_to_timing->assign_to_timing_id)
                    ->first();
                if ($user_in_one_of_graders_sections) {
                    $beauty_mail = app()->make(\Snowfire\Beautymail\Beautymail::class);
                    $to_email = $grader['email'];
                    $grading_info = ['course_name' => $course->name,
                        'assignment_name' => $assign_to_timing->assignment_name,
                        'grading_link' => request()->getSchemeAndHttpHost() . "/assignments/$assign_to_timing->assignment_id/grading"];

                    $beauty_mail->send('emails.notify_grader_when_assignment_due', $grading_info, function ($message)
                    use ($to_email) {
                        $message
                            ->from('adapt@libretexts.org')
                            ->to($to_email)
                            ->subject('Grading');
                    });
                }
            }
        }
    }
}
