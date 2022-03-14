<?php

namespace App\Http\Controllers;

use App\Exceptions\Handler;
use App\JWE;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Traits\JWT;

class ImathasController extends Controller
{

    use JWT;
    public function index(Request $request)
    {
        try {
            $response['type'] = 'error';
            $JWE = new JWE();
            //set up the problemJWT where you add information that you would like to persist
            $custom_claims = ['adapt' => [
                'assignment_id' => 1,
                'question_id' => 2]];

            $custom_claims['scheme_and_host'] = $request->getSchemeAndHttpHost();

            $custom_claims['imathas'] = [];
            $custom_claims['imathas']['id'] = '00000001'; //this will change
            $custom_claims['imathas']['seed'] = 1234; //can change if you like
            $custom_claims['imathas']['allowregen'] = false;//don't let them try similar problems
            $problemJWT = $this->createProblemJWT($JWE, $custom_claims, 'webwork');//need to create secret key for imathas as well
            $response['src'] = "https://imathas.libretexts.org/imathas/dev/embedq2.php?problemJWT=$problemJWT";
            $response['type'] = 'success';
        } catch (Exception $e){
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error creating the question.";
        }
        return $response;
    }

    public
    function createProblemJWT(JWE $JWE, array $custom_claims, string $technology)
    {
        $secret = $JWE->getSecret($technology);
        \JWTAuth::getJWTProvider()->setSecret($secret); //change the secret
        $token = \JWTAuth::getJWTProvider()->encode( $custom_claims); //create the token
        return $JWE->encrypt($token, 'webwork'); //create the token

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
            $technology = 'webwork';
            $JWE = new JWE();
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
            Log::info($problemJWT);
            Log::info(json_encode($answerJWT));

            //Process and save to your database!

            $response['type'] ='success';
            $response['message'] = ['answerJWT' => $answerJWT,'decodedProblemJWT' => json_decode($problemJWT)];
        } catch (Exception $e) {

            $h = new Handler(app());
            $h->report($e);

            $response['type'] = 'error';
            $response['status'] = 400;
            $response['message'] = "There was an error with this submission:  " . $e->getMessage();

        }
        return $response;
    }

}
