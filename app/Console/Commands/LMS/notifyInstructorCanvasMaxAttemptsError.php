<?php

namespace App\Console\Commands\LMS;

use App\CanGiveUp;
use App\CanvasMaxAttemptsError;
use App\Exceptions\Handler;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Snowfire\Beautymail\Beautymail;

class notifyInstructorCanvasMaxAttemptsError extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notify:instructorCanvasMaxAttemptsError';

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
     * @param CanvasMaxAttemptsError $canvasMaxAttemptsError
     * @return int
     * @throws Exception
     */
    public function handle(CanvasMaxAttemptsError $canvasMaxAttemptsError): int
    {
        try {
            $emails_to_be_sent = DB::table('canvas_max_attempts_errors')
                ->join('assignments', 'canvas_max_attempts_errors.assignment_id', '=', 'assignments.id')
                ->join('courses', 'assignments.course_id', '=', 'courses.id')
                ->join('users', 'courses.user_id', '=', 'users.id')
                ->select('courses.name AS course_name',
                    'assignments.name AS assignment_name',
                    'assignments.id AS assignment_id',
                    'users.id AS user_id',
                    'users.email',
                    'users.first_name')
                ->where('sent_email', 0)
                ->get();

            $emails_to_be_sent_by_user = [];
            foreach ($emails_to_be_sent as $value) {
                $user_id = $value->user_id;
                if (!isset($emails_to_be_sent_by_user[$user_id])) {
                    $emails_to_be_sent_by_user[$user_id] = ['first_name' => $value->first_name,
                        'email' => $value->email];
                    $emails_to_be_sent_by_user[$user_id]['assignment_info'] = [];
                }
                $emails_to_be_sent_by_user[$user_id]['assignment_info'][] = [
                    'course_assignment' => "$value->course_name --- $value->assignment_name",
                    'assignment_id' => $value->assignment_id];

            }
            foreach ($emails_to_be_sent_by_user as $email_info) {
                $email_info['plural'] = count($email_info['assignment_info']) > 1 ? 's' : '';
                $email_info['course_assignment'] = '';
                $assignment_ids = [];
                foreach ($email_info['assignment_info'] as$assignment_info) {
                    $email_info['course_assignment'] .= "{$assignment_info['course_assignment']}<br>";
                    $assignment_ids[] = $assignment_info['assignment_id'];
                }
                $beauty_mail = app()->make(Beautymail::class);
                try {
                    $beauty_mail->send('emails.notify_instructor_of_canvas_max_attempts_error', $email_info, function ($message)
                    use ($email_info) {
                        $message
                            ->from('adapt@noreply.libretexts.org', 'ADAPT')
                            ->to($email_info['email'])
                            ->subject('ADAPT to Canvas grade passback failed');
                    });
                    $canvasMaxAttemptsError->whereIn('assignment_id', $assignment_ids)
                        ->update(['sent_email' => 1]);
                } catch (Exception $e) {
                    $h = new Handler(app());
                    $h->report($e);
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
