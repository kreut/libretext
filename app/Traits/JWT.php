<?php


namespace App\Traits;


use Exception;
use Illuminate\Support\Facades\Log;
use Jose\Component\Core\JWK;
use Jose\Easy\Load;
use Jose\Component\KeyManagement\JWKFactory;


trait JWT
{

    public function getPayload($token, $secret) {
        // TODO: Refactor secret here.
        $jwk = JWKFactory::createFromSecret(
       $secret
        );

        ///TODO: test a junk token!!!!!!!
        /// TODO: refactor and get imathAS as well
        $jwt = Load::jws($token) // We want to load and verify the token in the variable $token
        ->algs(['HS256']) // The algorithms allowed to be used
        // ->exp() // We check the "exp" claim
        // ->iat(1000) // We check the "iat" claim. Leeway is 1000ms (1s)
        // ->nbf() // We check the "nbf" claim
        // ->aud('audience1') // Allowed audience
        // ->iss('issuer') // Allowed issuer
        // ->sub('subject') // Allowed subject
        // ->jti('0123456789') // Token ID
        ->key($jwk) // Key used to verify the signature
        ->run(); // Go!
        $jwt = (object)$jwt->claims->all();

        return $jwt;

        // $tokenParts = explode(".", $token);
        // $tokenHeader = base64_decode($tokenParts[0]);
        // $tokenPayload = base64_decode($tokenParts[1]);
        // // $tokenPayload = str_replace("'", '"', $tokenPayload);
        // $jwtPayload = json_decode($tokenPayload, false, 512, JSON_UNESCAPED_UNICODE);
        // if (!$jwtPayload) {
        //     Log::info($tokenPayload);
        //     throw new Exception('JWT Payload does not exist: ' . $token);
        // }
        // return $jwtPayload;
    }

}
