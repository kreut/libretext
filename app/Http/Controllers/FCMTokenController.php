<?php

namespace App\Http\Controllers;

use App\Exceptions\Handler;
use App\FCMLog;
use App\FCMToken;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Kreait\Firebase\Exception\FirebaseException;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FCMTokenController extends Controller
{
    public function __construct(Messaging $messaging)
    {
        $this->messaging = $messaging;
    }

    /**
     * @param Request $request
     * @param FCMToken $FCMToken
     * @return array
     * @throws Exception
     */
    public function store(Request $request, FCMToken $FCMToken): array
    {
        $response['type'] = 'error';
        try {
            if (!$request->fcm_token) {
                throw new Exception ("No token in the request.");
            }
            if (!DB::table('fcm_tokens')
                ->where('user_id', $request->user()->id)
                ->where('fcm_token', $request->fcm_token)
                ->first()) {
                $FCMToken->user_id = $request->user()->id;
                $FCMToken->fcm_token = $request->fcm_token;
                $FCMToken->save();
            }
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = $e->getMessage();
        }
        return $response;
    }

    /**
     * @param Request $request
     * @param FCMToken $FCMToken
     * @return void
     * @throws Exception
     */
    public function testSendNotification(Request $request, FCMToken $FCMToken)
    {

        $fcm_tokens = $FCMToken->where('user_id', 3055)->get();
        $title = 'Final message';
        $body = 'Final message body';
        foreach ($fcm_tokens as $fcm_token) {
            try {
                $notification = Notification::create($title, $body);
                $response = CloudMessage::withTarget('token', $fcm_token->fcm_token)
                    ->withNotification($notification);
                $response = json_encode($response->jsonSerialize());
            } catch (Exception $e) {
                $response = $e->getMessage();
            }
            $fcmLog = new FCMLog();
            $fcmLog->user_id = $fcm_token->user_id;
            $fcmLog->response = $response;
            $fcmLog->save();
        }
        /* $report = $this->messaging->sendMulticast($message, $deviceTokens);
         echo 'Successful sends: ' . $report->successes()->count() . PHP_EOL;
         //echo 'Failed sends: ' . $report->failures()->count() . PHP_EOL;

         if ($report->hasFailures()) {
             foreach ($report->failures()->getItems() as $failure) {
                 echo $failure->error()->getMessage() . PHP_EOL;
             }
         }

// Unknown tokens are tokens that are valid but not know to the currently
// used Firebase project. This can, for example, happen when you are
// sending from a project on a staging environment to tokens in a
// production environment
         $unknownTargets = $report->unknownTokens(); // string[]
         var_dump($unknownTargets);
// Invalid (=malformed) tokens
         $invalidTargets = $report->invalidTokens(); // string[]
         var_dump($invalidTargets);
         var_dump($report);
         // $result = $this->messaging->send($message);


         // $FCMToken->sendNotification(3055);
        */

    }

}
