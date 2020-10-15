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
        $token = $JWE->encrypt('eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOlwvXC8xMjcuMC4wLjE6ODAwMFwvYXBpXC9sb2dpbiIsImlhdCI6MTYwMjU5NTM5MSwiZXhwIjoyNDY2NTk1MzkxLCJuYmYiOjE2MDI1OTUzOTEsImp0aSI6IlV5alhWSlRzdHdmbkNyZjEiLCJzdWIiOjEsInBydiI6Ijg3ZTBhZjFlZjlmZDE1ODEyZmRlYzk3MTUzYTE0ZTBiMDQ3NTQ2YWEifQ.-o0_89Kc5dqt58pbFGw4AktqrndDPb_L5lEmRY4Vqes');
        echo "The encrypted token: " . $token;
        echo "The decrypted token: " . $JWE->decrypt('eyJwMnMiOiJoSVprUURjRGhsaHhHcGZoSlE0eFpUQ3Y0UER5bmJwN3pRMUxJaXB4d1BIdEktX0NDSzhyeTFlMGJRbEQxa0NkeS1ZUy1qcndXTzc5V3JFSXhFTWJyZyIsInAyYyI6NDA5NiwiYWxnIjoiUEJFUzItSFM1MTIrQTI1NktXIiwiZW5jIjoiQTI1NkdDTSIsInppcCI6IkRFRiJ9.MI8w-3sW6H6nkY_IGIj_PJkbPp_tP4Vz222XukJrrm1HGD5HH-nYTg.hl2NGPjGbYqE6B9n.0vD1ps-m9megozlEgnAQ1GPOBnVe34xHqOELoZqAGi33bKKB2eujpmDcxR1d9jLA3wSabN2YygXWC_03nEzRAyu3T3Oe3_5pM4TOf7s.fSk977KyZQrLMDYV_Bbxhg');
    }

    public function validateToken($request)
    {
        //Webwork should post the answerJWT with Authorization using the Adapt JWT
        $response['type'] = 'error';
        try {
            if (!$user = auth()->setRequest($request)->user()) {
                $response['message'] = 'User not found';
            } else {
                $response['type'] = 'success';
            }

        } catch (\Exception $e) {
            $response['message'] = $e->getMessage();
        }
        return $response;
    }


    public function processAnswerJWT(Request $request)
    {

        $content = $request->getContent();
        Log::info($content);
        $response = $this->validateToken($request);
        if ($response['type'] === 'error') {
            return json_encode($response);
        }


        $answerJWT = $this->getPayload($content);
//if the token isn't formed correctly return a message
        if (!isset($answerJWT->problemJWT)) {
            $message = "You are missing the problemJWT in your answerJWT!";
            return json_encode(['type' => 'error', 'message' => $message]);
        }

        $problemJWT = $this->getPayload($answerJWT->problemJWT);

        $missing_properties = !(
            isset($problemJWT->adapt) &&
            isset($problemJWT->adapt->assignment_id) &&
            isset($problemJWT->adapt->question_id) &&
            isset($problemJWT->adapt->technology));
        if ($missing_properties) {
            $message = "The problemJWT has an incorrect structure.  Please contact us for assistance.";
            return json_encode(['type' => 'error', 'message' => $message]);
        }

        if (!in_array($problemJWT->adapt->technology, ['webwork', 'imathas'])) {
            $message = $problemJWT->adapt->technology . " is not an accepted technology.  Please contact us for assistance.";
            return json_encode(['type' => 'error', 'message' => $message]);
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

