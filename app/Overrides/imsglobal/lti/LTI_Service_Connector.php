<?php
namespace Overrides\IMSGlobal\LTI;

use App\Exceptions\Handler;
use App\LtiRegistration;
use Exception;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class LTI_Service_Connector
{

    const NEXT_PAGE_REGEX = "/^Link:.*<([^>]*)>; ?rel=\"next\"/i";

    private $registration;
    private $access_tokens = [];

    public function __construct(LTI_Registration $registration)
    {
        $this->registration = $registration;
    }

    public function get_access_token($scopes)
    {

        // Don't fetch the same key more than once.
        sort($scopes);
        $scope_key = md5(implode('|', $scopes));
        if (isset($this->access_tokens[$scope_key])) {
            return $this->access_tokens[$scope_key];
        }

        // Build up JWT to exchange for an auth token
        $client_id = $this->registration->get_client_id();
        $lti_registration = LtiRegistration::where('iss', $client_id)->first();
        //Storage::disk('s3')->put("lti_registration.txt", $this->registration->get_auth_token_url());
        //Storage::disk('s3')->put("issuer.txt", $this->registration->get_issuer());
        $issuer = $this->registration->get_issuer();
        switch ($issuer) {
            case('https://blackboard.com'):
            case('https://canvas.instructure.com'):
            case('https://canvas.test.instructure.com'):
                $aud = $this->registration->get_auth_token_url();
                break;
            case('https://dev-canvas.libretexts.org'):
                $aud = $lti_registration ? $lti_registration->auth_token_url : $this->registration->get_auth_token_url();
                break;
            default:
                throw new Exception($this->registration->get_issuer() . " is not valid.");
        }
        $jwt_claim = [
            "iss" => $client_id,
            "sub" => $client_id,
            "aud" => $aud, //ADAPT: changed from $this->registration->get_auth_server()   */
            "iat" => time() - 5,
            "exp" => time() + 60,
            "jti" => 'lti-service-token' . hash('sha256', random_bytes(64))
        ];

        // Sign the JWT with our private key (given by the platform on registration)
        $jwt = JWT::encode($jwt_claim, $this->registration->get_tool_private_key(), 'RS256', $this->registration->get_kid());

        // Build auth token request headers
        $auth_request = [
            'grant_type' => 'client_credentials',
            'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
            'client_assertion' => $jwt,
            'scope' => implode(' ', $scopes)
        ];

        // Make request to get auth token
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->registration->get_auth_token_url());
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($auth_request));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $resp = curl_exec($ch);
        $token_data = json_decode($resp, true);
        curl_close($ch);
        if (isset($token_data['error'])) {
            throw new Exception ("Grade pass back failed for issuer $issuer:" . $token_data['error_description']);

        }
        return $this->access_tokens[$scope_key] = $token_data['access_token'];
    }

    public function make_service_request($scopes, $method, $url, $body = null, $content_type = 'application/json', $accept = 'application/json')
    {
        try {
            $ch = curl_init();
            $headers = [
                'Authorization: Bearer ' . $this->get_access_token($scopes),
                'Accept:' . $accept,
            ];
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            if ($method === 'POST') {
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, strval($body));
                $headers[] = 'Content-Type: ' . $content_type;
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $response = curl_exec($ch);
            if (curl_errno($ch)) {
                throw new Exception ('Make Service Request Error:' . curl_error($ch));
            }
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $ch = curl_init();
            $headers = [
                'Authorization: Bearer ' . $this->get_access_token($scopes),
                'Accept:' . $accept,
            ];
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);//retry without verifying
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

            if ($method === 'POST') {
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, strval($body));
                $headers[] = 'Content-Type: ' . $content_type;
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $response = curl_exec($ch);
            if (curl_errno($ch)) {
                throw new Exception ('Make Service Request Error:' . curl_error($ch));
            }


        }
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        $resp_headers = substr($response, 0, $header_size);
        $resp_body = substr($response, $header_size);
        return [
            'headers' => array_filter(explode("\r\n", $resp_headers)),
            'body' => json_decode($resp_body, true),
        ];
    }
}

?>
