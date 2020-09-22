<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\Http\Requests\StoreSubmission;
use App\JWTModel;
use App\Score;
use App\Submission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Traits\JWT;

class JWTController extends Controller
{
use JWT;

    public function init()
    {
        $JWTModel = new JWTModel();
        $token = $JWTModel->encode('My really secret payload that only Henry knows.');
        echo "The encrypted token: " . $token;
        echo "The decrypted token: " . $JWTModel->decode($token);
    }

    public function validateToken()
    {
        //Webwork should post the answerJWT with Authorization using the Adapt JWT
        try {
            if (!$user = \JWTAuth::parseToken()->authenticate()) {
                return response()->json(['user_not_found'], 404);
            }

        } catch (Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {

            return response()->json(['token_expired'], $e->getStatusCode());

        } catch (Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {

            return response()->json(['token_invalid'], $e->getStatusCode());

        } catch (Tymon\JWTAuth\Exceptions\JWTException $e) {

            return response()->json(['token_absent'], $e->getStatusCode());

        }
        return \JWTAuth::parseToken()->getPayload();
    }


    public function processAnswerJWT()
    {

        $payload= $this->validateToken();//get the payload
        $answerJWT  = json_decode($payload);//convert it to an array
        $problemJWT = $this->getPayload($answerJWT->problemJWT);//inside the answer JWT

        $request = new storeSubmission();
        $request['assignment_id'] =  $problemJWT->adapt->assignment_id;
        $request['question_id'] =  $problemJWT->adapt->question_id;
        $request['technology'] =  $problemJWT->adapt->technology;
        $request['submission'] =  $answerJWT;
        $Submission = new Submission();
        return $Submission->store($request, new Submission(), new Assignment(), new Score());
    }

}

