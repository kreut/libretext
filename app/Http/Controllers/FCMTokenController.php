<?php

namespace App\Http\Controllers;

use App\Exceptions\Handler;
use App\FCMToken;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FCMTokenController extends Controller
{
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
        try {
            $FCMToken->sendNotification($request->user()->id);
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
        }
    }

}
