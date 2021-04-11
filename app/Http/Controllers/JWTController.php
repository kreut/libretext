<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\DataShop;
use App\Exceptions\Handler;
use App\Http\Requests\StoreSubmission;
use App\JWE;
use App\LtiLaunch;
use App\LtiGradePassback;
use App\Score;
use App\Submission;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
        echo "The decrypted token: " . $JWE->decrypt($token);
    }

    public function signWithNewSecret()
    {
        //encoding...
        //


        $payload = auth()->payload();
        print_r($payload->toArray()); // current user info
        \JWTAuth::getJWTProvider()->setSecret('secret'); //change the secret
        $claims = ['foo' => 'bar'];//create the claims
        $token = \JWTAuth::getJWTProvider()->encode(array_merge($claims, $payload->toArray())); //create the token


        //set the same secret for encoding
        $secret = file_get_contents(base_path() . '/JWE/webwork');
        \JWTAuth::getJWTProvider()->setSecret($secret);
        //$decoded = \JWTAuth::getJWTProvider()->decode($token);
        auth()->setToken($token)->getPayload();
        var_dump(auth()->user());
    }


    function base64UrlEncode($text)
    {
        return str_replace(
            ['+', '/', '='],
            ['-', '_', ''],
            base64_encode($text)
        );
    }

    /**
     * @param $content
     * @param $secret
     * @return bool
     */
    public function validateSignature($content, $secret): bool
    {

        //https://developer.okta.com/blog/2019/02/04/create-and-verify-jwts-in-php
        //verify
        // split the token
        $tokenParts = explode('.', $content);
        if (!(isset($tokenParts[0]) && isset($tokenParts[1]) && isset($tokenParts[2]))) {
            return false;
        }
        $header = $tokenParts[0];
        $payload = $tokenParts[1];
        $signatureProvided = $tokenParts[2];
        $signature = hash_hmac('sha256', $header . "." . $payload, $secret, true);
        $base64UrlSignature = $this->base64UrlEncode($signature);

        // verify it matches the signature provided in the token
        return ($base64UrlSignature === $signatureProvided);
        //
    }

    public function processAnswerJWT(Request $request)
    {
        try {
            $JWE = new JWE();
            //$referer = $request->headers->get('referer');//will use this to determine the technology
            $technology = 'webwork';

            $secret = $JWE->getSecret($technology);
            \JWTAuth::getJWTProvider()->setSecret($secret);
            $content = $request->getContent();

            // Log::info('Content:' . $content);
            if (!$this->validateSignature($content, $secret)) {
                throw new Exception("Your JWT does not have a valid signature.");
            }
            $answerJWT = $this->getPayload($content, $secret);

            if (!isset($answerJWT->problemJWT)) {
                Log::info('Answer JWT:' . json_encode($answerJWT));
                throw new Exception("You are missing the problemJWT in your answerJWT!");
            }

            $jwe = new JWE();
            $problemJWT = $jwe->decrypt($answerJWT->problemJWT, $technology);
            //  Log::info('Problem JWT:' .json_encode($problemJWT));
            $token = \JWTAuth::getJWTProvider()->encode(json_decode($problemJWT, true));
            //Log::info($token);
            if (!auth()->setToken($token)->getPayload()) {
                throw new Exception('User not found');
            }

//if the token isn't formed correctly return a message

            $problemJWT = json_decode($problemJWT);
            $missing_properties = !(
                isset($problemJWT->adapt) &&
                isset($problemJWT->adapt->assignment_id) &&
                isset($problemJWT->adapt->question_id) &&
                isset($problemJWT->adapt->technology));
            if ($missing_properties) {
                throw new Exception("The problemJWT has an incorrect structure.  Please contact us for assistance.");
            }

            if (!in_array($problemJWT->adapt->technology, ['webwork', 'imathas'])) {
                throw new Exception($problemJWT->adapt->technology . " is not an accepted technology.  Please contact us for assistance.");
            }
            if ($problemJWT->adapt->technology === 'webwork' && isset($answerJWT->score['answers'])) {
                $answers = $answerJWT->score['answers'];
                foreach ($answers as $key => $value) {
                    if ($answers[$key]['error_message']) {
                        throw new Exception ("At least one of your submitted responses is invalid.  Please fix it and try again.");
                    }
                }
            }
            //good to go!
            $request = new storeSubmission();
            $request['assignment_id'] = $problemJWT->adapt->assignment_id;
            $request['question_id'] = $problemJWT->adapt->question_id;
            $request['technology'] = $problemJWT->adapt->technology;
            $request['submission'] = $answerJWT;

            if (($request['technology'] === 'webwork') && $answerJWT->score === null) {
                throw new Exception('Score field was null.');
            }
            $Submission = new Submission();
            return $Submission->store($request, new Submission(), new Assignment(), new Score(), new LtiLaunch(), new LtiGradePassback(), new DataShop());

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['type'] = 'error';
            $response['status'] = 400;
            $response['message'] = "There was an error with this submission:  " . $e->getMessage();
            return $response;
        }
    }

}
