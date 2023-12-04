<?php

namespace App\Custom;

use App\Assignment;
use App\Exceptions\Handler;
use Exception;
use Illuminate\Support\Facades\DB;
use Kreait\Firebase\Messaging;

class FCMNotification
{
    public function __construct(Messaging $messaging)
    {
        $this->messaging = $messaging;
    }

    public function sendNotificationsByAssignment(Assignment $assignment, array $message)
    {
        try {
            $fcm_tokens = DB::table('enrollments')
                ->join('fcm_tokens', 'enrollments.user_id', '=', 'fcm_tokens.user_id')
                ->where('course_id', $assignment->course->id)
                ->select('fcm_token')
                ->get()
                ->pluck('fcm_token')
                ->toArray();
            if ($fcm_tokens) {
                $this->messaging->sendMulticast($message, $fcm_tokens);
            }
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);

        }

    }
}
