<?php

namespace App\Console\Commands\Notifications;

use App\Exceptions\Handler;
use App\StudentEmailsWithSubmission;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Snowfire\Beautymail\Beautymail;

class emailStudentsWithSubmissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:studentsWithSubmissions';

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
     * @return int
     * @throws Exception
     */
    public function handle()
    {
        $student_emails_with_submissions = StudentEmailsWithSubmission::where('status', 'pending')->get();

        $beauty_mail = app()->make(Beautymail::class);
        foreach ($student_emails_with_submissions as $student_email_with_submission) {
            try {
                $student_email_with_submission_arr = $student_email_with_submission->toArray();

                $student_email_with_submission_arr['email_message'] = $student_email_with_submission['message'];
                $beauty_mail->send('emails.student_email_with_submission', $student_email_with_submission_arr, function ($message)
                use ($student_email_with_submission_arr) {
                    $message
                        ->from('adapt@noreply.libretexts.org', 'ADAPT')
                        ->replyTo($student_email_with_submission_arr['instructor_email'], $student_email_with_submission_arr['instructor_name'])
                        ->to($student_email_with_submission_arr ['student_email'], $student_email_with_submission_arr['student_name'])
                        ->subject('Re-submit ADAPT Assignment Question');
                });
                $student_email_with_submission->status = 'sent';

            } catch (Exception $e) {
                $student_email_with_submission->status = 'error';
                $h = new Handler(app());
                $h->report($e);
            }
            $student_email_with_submission->save();
        }
        return 0;
    }
}
