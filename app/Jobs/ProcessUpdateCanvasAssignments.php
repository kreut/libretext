<?php

namespace App\Jobs;

use App\CanvasAPI;
use App\Course;
use App\User;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Snowfire\Beautymail\Beautymail;

class ProcessUpdateCanvasAssignments implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Course
     */
    private $course;
    /**
     * @var string
     */
    private $property;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Course $course, string $property)
    {
        $this->course = $course;
        $this->property = $property;

    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws Exception
     */
    public function handle()
    {
        $lti_registration = $this->course->getLtiRegistration();
        $canvasAPI = new CanvasAPI($lti_registration, $this->course->user_id);

        $response = $canvasAPI->updateCanvasAssignments($this->course, $this->property);
        $email_info = [
            'some_message' => $response['message'],
            'subject' => $this->property === 'points' ? "ADAPT to Canvas points update" : "ADAPT to Canvas timings update",
            'email' => User::find($this->course->user_id)->email];
        Log::info($response['message']);
        $beauty_mail = app()->make(Beautymail::class);
        $to = $email_info['email'];
        $subject = $email_info['subject'];
        $beauty_mail->send('emails.notify_instructor_of_updated_canvas_property', $email_info, function ($message)
        use ($to, $subject) {
            $message
                ->from('adapt@noreply.libretexts.org', 'ADAPT')
                ->replyTo('adapt@libretexts.org', 'ADAPT')
                ->to($to)
                ->subject($subject);
        });
    }
}
