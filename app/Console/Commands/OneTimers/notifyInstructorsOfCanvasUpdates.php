<?php

namespace App\Console\Commands\OneTimers;

use App\Exceptions\Handler;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Snowfire\Beautymail\Beautymail;

class notifyInstructorsOfCanvasUpdates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notify:InstructorsOfCanvasUpdates';

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
     */
    public function handle()
    {
        $courses_by_user_id = [];
        try {
            $courses = DB::table('courses')
                ->join('users', 'courses.user_id', '=', 'users.id')
                ->whereNotNull('lms_course_id')
                ->select('users.id AS user_id', 'users.email', 'users.first_name', 'courses.name AS course_name')
                ->get();

            foreach ($courses as $course) {
                if (!isset($courses_by_user_id[$course->user_id])) {
                    $courses_by_user_id[$course->user_id] = [
                        'name' => $course->first_name,
                        'email' => $course->email];
                    $courses_by_user_id[$course->user_id]['courses'] = [];
                }
                $courses_by_user_id[$course->user_id]['courses'][] = $course->course_name;
            }
            $courses_by_user_id = array_values($courses_by_user_id);
            foreach ($courses_by_user_id as $key => $email_info) {
                if (!in_array($email_info['email'], ['najmah.muhammad@estrellamountain.edu', 'hfchen@ksu.edu'])) {
                    $beauty_mail = app()->make(Beautymail::class);
                    try {
                        $beauty_mail->send('emails.notify_instructor_of_canvas_updates', $email_info, function ($message)
                        use ($email_info) {
                            $message
                                ->from('adapt@noreply.libretexts.org', 'Eric Kean')
                                ->to($email_info['email'], $email_info['name'])
                                ->subject('ADAPT to Canvas grade passback failed');
                        });
                    } catch (Exception $e) {
                        $h = new Handler(app());
                        $h->report($e);
                    }
                }  else {
                    unset($courses_by_user_id[$key]);
                }
            }


            print_r($courses_by_user_id);
        } catch (Exception $e) {
            echo $e->getMessage();
        }

        return 0;
    }
}
