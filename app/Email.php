<?php

namespace App;

use App\Exceptions\Handler;
use \Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use \Illuminate\Http\Request;

class Email extends Model
{
    /**
     * @throws Exception
     */
    public function sendAssignmentDueReminders()
    {

        $dt = new \DateTime(now(), new \DateTimeZone('UTC'));
        $dt->setTimeZone(new \DateTimeZone('America/Los_Angeles'));

        $time = $dt->format('Y-m-d H:i:s');
        Log::info($time);
        $notifications = DB::table('notifications')
            ->join('users', 'notifications.user_id', '=', 'users.id')
            ->join('enrollments', 'users.id', '=', 'enrollments.user_id')
            ->join('assignments', 'enrollments.course_id', '=', 'assignments.course_id')
            ->join('courses', 'assignments.course_id', '=', 'courses.id')
            ->where('assignments.due', DB::raw("DATE_FORMAT(DATE_ADD(UTC_TIMESTAMP(), INTERVAL notifications.hours_until_due HOUR), '%Y-%m-%d %H:%i:00')"))
            ->select('users.first_name', 'users.last_name', 'users.email',
                DB::raw('assignments.name AS assignment_name'), DB::raw('assignments.id AS assignment_id'),
                DB::raw('courses.name AS course_name'),
                'notifications.hours_until_due')
            ->get();
        foreach ($notifications as $notification) {
            Log::info($time . ': ' . json_encode($notification));

            try {
                $mail_info = [
                    'student_first_name' => $notification->first_name,
                    'assignment' => $notification->assignment_name,
                    'course' => $notification->course_name,
                    'hours_until_due' => $notification->hours_until_due > 1 ? "{$notification->hours_until_due} hours" : "{$notification->hours_until_due} hour",
                    'assignment_link' => env('APP_URL') . "/students/assignments/{$notification->assignment_id}/summary"
                ];

                $beautymail = app()->make(\Snowfire\Beautymail\Beautymail::class);
                $beautymail->send('emails.assignment_due_reminder', $mail_info, function ($message)
                use ($notification) {
                    $message->from('adapt@libretexts.org')
                        ->to($notification->email, $notification->first_name)
                        ->subject('An Upcoming Assignment Is Due');
                });

            } catch (Exception $e) {
                $h = new Handler(app());
                $h->report($e);
            }
        }


    }

}
