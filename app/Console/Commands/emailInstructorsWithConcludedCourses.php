<?php

namespace App\Console\Commands;

use App\Course;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Foundation\Exceptions\Handler;
use Snowfire\Beautymail\Beautymail;

class emailInstructorsWithConcludedCourses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:instructorsWithConcludedCourses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Emails instructors after their courses have been concluded for too long';

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
     * @param Course $course
     * @return int
     * @throws \Throwable
     */
    public function handle(Course $course): int
    {
        try {
            $num_days = [30, 60, 90, 100];

            foreach ($num_days as $num_day) {
                $concluded_courses = $course->concludedCourses('equals', $num_day);
                if ($concluded_courses->isNotEmpty()) {
                    $beauty_mail = app()->make(Beautymail::class);
                    foreach ($concluded_courses as $concluded_course) {
                        $concluded_course->num_days = $num_day;
                        $concluded_course->reset_course_link = request()->getSchemeAndHttpHost() . "/instructors/courses/$concluded_course->id/properties/reset";
                        $beauty_mail->send('emails.reset_course', (array)$concluded_course, function ($message)
                        use ($concluded_course) {
                            $message
                                ->from('adapt@noreply.libretexts.org', 'ADAPT')
                                ->to($concluded_course->email)
                                ->subject('Grading');
                        });
                        if ($num_day === 100) {
                            $concluded_course->courses_to_reset_link = request()->getSchemeAndHttpHost() . "/control-panel/courses-to-reset";
                            $beauty_mail->send('emails.100_day_reset_course', (array)$concluded_course, function ($message)
                            use ($concluded_course) {
                                $message
                                    ->from('adapt@noreply.libretexts.org', 'ADAPT')
                                    ->to('delmar@libretexts.org')
                                    ->subject("$concluded_course->name should be reset");
                            });
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            return 1;
        }
        return 0;
    }
}
