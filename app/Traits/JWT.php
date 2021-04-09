<?php


namespace App\Traits;


use Exception;
use Illuminate\Support\Facades\Log;

trait JWT
{

    public function getPayload($token) {
        $tokenParts = explode(".", $token);
        $tokenHeader = base64_decode($tokenParts[0]);
        $tokenPayload = base64_decode($tokenParts[1]);
        Log::info($tokenPayload);
        Log::info( mb_detect_encoding($tokenPayload));
        $jwtPayload = json_decode($tokenPayload, false);
        Log::info(json_last_error());
        if (!$jwtPayload) {
            throw new Exception('JWT Payload does not exist: ' . $token);
        }
        return $jwtPayload;
    }

}
