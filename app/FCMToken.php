<?php

namespace App;

use App\Services\FCMService;
use Illuminate\Database\Eloquent\Model;

class FCMToken extends Model
{
    protected $table = 'fcm_tokens';
    /**
     * @param $user_id
     * @return void
     */
    public function sendNotification($user_id)
    {
        //https://dev.to/rabeeaali/send-push-notifications-from-laravel-to-ios-android-29b4
        $fcm_tokens = $this->where('user_id', $user_id)->get();
        foreach ($fcm_tokens as $fcm_token) {
            $fcm_token->fcm_token = 'sdfds';
           $response =  FCMService::send(
                $fcm_token->fcm_token,
                [
                    'title' => 'Test',
                    'body' => 'I cannot believe this actually worked.',
                ]
            );
           dd($response->body());
        }
    }
}
