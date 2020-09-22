<?php

namespace App\Http\Controllers;
use App\JWTModel;


class JWTController extends Controller
{

    public function init() {
        $JWTModel = new JWTModel();
        $token=  $JWTModel ->encode('My really secret payload that only Henry knows.');
        echo "The encrypted token: " .    $token;
        echo "The decrypted token: " . $JWTModel->decode($token);
    }


}

