<?php


namespace App\Traits;


trait JWT
{
    public function getPayload($token) {
        $tokenParts = explode(".", $token);
        $tokenHeader = base64_decode($tokenParts[0]);
        $tokenPayload = base64_decode($tokenParts[1]);
        $jwtPayload = json_decode($tokenPayload);
        return $jwtPayload;
    }

}
