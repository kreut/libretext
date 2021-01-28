<?php

namespace App;

use App\Exceptions\Handler;
use \Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class Email extends Model
{
    /**
     * @throws Exception
     */
    public function sendAssignmentDueReminders()
    {

        $student_first_name = 'Mike';
        $student_user = new \stdClass();
        $student_user->email ='kreut@hotmail.com';
        $student_user->name  = 'Mike bike';

        $assignment = 'some assignment';
        $course = 'some course';
        $hours_until_due = '2 hours';
        $assignment_link = 'www.adapt.libretexts.org/assignment';

        try {

            $mail_info = [
                'student_first_name' => $student_first_name,
                'assignment' => $assignment,
                'course' => $course,
                'hours_until_due' => $hours_until_due,
                'assignment_link' => $assignment_link
            ];

            $beautymail = app()->make(\Snowfire\Beautymail\Beautymail::class);
            $beautymail->send('emails.assignment_due_reminder', $mail_info, function($message)
            use ($student_user) {
                $message
                    ->from('adapt@libretexts.org')
                    ->to('support@itsmorethanatextbook.com','Eric Kean')
                    ->subject('An Upcoming Assignment Is Due');
            });

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);

        }


    }

}
