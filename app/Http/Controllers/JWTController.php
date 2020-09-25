<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\Http\Requests\StoreSubmission;
use App\JWE;
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
        $JWE = new JWE();
        $token = $JWE->encode('My really secret payload that only Henry knows.');
        echo "The encrypted token: " . $token;
        echo "The decrypted token: " . $JWE->decode($token);
    }

    public function validateToken(Request $request)
    {

        //Webwork should post the answerJWT with Authorization using the Adapt JWT
        try {
            ;
            if (!$user = \JWTAuth::parseToken()->authenticate()) {
                return json_encode(['type' => 'error', 'message' => 'User not found', 'token' => $request->header('Authorization')]);
            }

        } catch (\Exception $e) {
            return json_encode(['type' => 'error', 'message' => $e->getMessage(), 'token' => $request->header('Authorization')]);
        }
        return \JWTAuth::parseToken()->getPayload();
    }


    public function processAnswerJWT(Request $request)
    {

        $payload = $this->validateToken($request);//get the payload
        $answerJWT = json_decode($payload);//convert it to an object
        //if the token was bad return a message
        if (isset($answerJWT->type) && $answerJWT->type === 'error') {
            return $payload;
        }
//if the token isn't formed correctly return a message
        if (!isset($answerJWT->problemJWT)) {
            $message = "You are missing the problemJWT in your answerJWT!";
            return json_encode(['type' => 'error', 'message' => $message, 'payload' => $payload]);
        }
        $problemJWT = $this->getPayload($answerJWT->problemJWT);//inside the answer JWT
        $missing_properties = !(
            isset($problemJWT->adapt) &&
            isset($problemJWT->adapt->assignment_id) &&
            isset($problemJWT->adapt->question_id) &&
            isset($problemJWT->adapt->technology));
        if ($missing_properties) {
            $message = "The problemJWT has an incorrect structure.  Please contact us for assistance.";
            return json_encode(['type' => 'error', 'message' => $message, 'payload' => $payload]);
        }

        //good to go!
        $request = new storeSubmission();
        $request['assignment_id'] = $problemJWT->adapt->assignment_id;
        $request['question_id'] = $problemJWT->adapt->question_id;
        $request['technology'] = $problemJWT->adapt->technology;
        $request['submission'] = $answerJWT;

        if (($request['technology'] === 'webwork') && $answerJWT->score === null) {
            $response['message'] = 'Score field was null.';
            $response['type'] = 'error';
            return $response;
        }
        $Submission = new Submission();
        return $Submission->store($request, new Submission(), new Assignment(), new Score());
    }

}

