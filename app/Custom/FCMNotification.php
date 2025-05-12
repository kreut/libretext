<?php

namespace App\Custom;

use App\Assignment;
use App\Exceptions\Handler;
use App\FCMToken;
use Exception;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class FCMNotification
{

    public function sendNotificationsByAssignment(Assignment $assignment, array $message)
    {
        $fcm_tokens = DB::table('enrollments')
            ->join('fcm_tokens', 'enrollments.user_id', '=', 'fcm_tokens.user_id')
            ->where('course_id', $assignment->course->id)
            ->get();
        $accessToken = $this->_getFirebaseAccessToken();
        if ($fcm_tokens) {
            foreach ($fcm_tokens as $fcm_token) {
                try {
                    $projectId = 'libretexts-adapt'; // from service account JSON
                    $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

                    $messagePayload = [
                        'message' => [
                            'token' => $fcm_token->fcm_token,
                            'notification' => $message,
                        ],
                    ];
                    // ['path' => "Assignment/$assignment->id/Question/$question->id"]

                    $http_response = Http::withToken($accessToken)
                        ->post($url, $messagePayload);

                    $response = $http_response->successful() ?
                        'Notification sent successfully.'
                        : 'FCM send failed: ' . $http_response->body();
                    FCMToken::create(['user_id' => $fcm_token->user_id, 'response' => $response]);
                } catch (Exception $e) {
                    $h = new Handler(app());
                    $h->report($e);
                }
            }
        }
    }

    private function _getFirebaseAccessToken(): string
    {
        $credentialsPath = config('myconfig.firebase_credentials');

        $scopes = ['https://www.googleapis.com/auth/firebase.messaging'];

        $credentials = new ServiceAccountCredentials($scopes, $credentialsPath);
        $token = $credentials->fetchAuthToken();
        return $token['access_token'];
    }

}
