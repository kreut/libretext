<?php

namespace App\Custom;

//require_once __DIR__ . '/../vendor/autoload.php';

use \IMSGlobal\LTI;

class LTIDatabase implements LTI\Database
{
    private $iss;
    private $private_key;
    private $client_id;
    public function __construct($request, $private_key, $client_id)
    {
        $this->iss = $request->iss;
$this->private_key = $private_key;
$this->client_id = $client_id;
        //dd('constructor');
       // $this->
       // $oidc_endpoint = 'https://canvas.instructure.com/api/lti/authorize_redirect';
       // $redirect_uri = '';
      //  $client_id = '10000000000002';//created by Canvas
     //  $login_hint = $request->login_hint;
        /**array:4 [▼
         * "iss" => "https://canvas.instructure.com"
         * "login_hint" => "f326d6a8a55f30f47b2480586f97991ab9e602bb"
         * "target_link_uri" => "https://dev.adapt.libretexts.org/api/lti/target-link-uri"
         * "lti_message_hint" => "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ2ZXJpZmllciI6bnVsbCwiY2FudmFzX2RvbWFpbiI6ImxvY2FsaG9zdCIsImNvbnRleHRfdHlwZSI6IkNvdXJzZSIsImNvbnRleHRfaWQiOjEwMDAwMDAwMDAwMDAxLCJleHAiOjE2MDU4MTgyMjl9.Ln6VDW8sY23mYh_AexlEBon3YGfQ07kmv6SAu8pN1S8 ◀"
         * ]*/

    }

    public function find_registration_by_issuer($iss)
    {

        return LTI\LTI_Registration::new()
            ->set_auth_login_url('https://dev-canvas.libretexts.org/api/lti/authorize')//authorized worked on dev!!!
            ->set_auth_token_url('https://dev-canvas.libretexts.org/login/oauth2/auth')
            ->set_auth_server('https://dev-canvas.libretexts.org')
            ->set_client_id($this->client_id)
            ->set_key_set_url('https://dev-canvas.libretexts.org/api/lti/security/jwks')
            ->set_kid('kid')
            ->set_issuer('https://dev-canvas.libretexts.org')
            ->set_tool_private_key($this->private_key($this->iss));
    }

    public function find_deployment($iss, $deployment_id)
    {
       // dd( $deployment_id);
      //  dd($_SESSION);
        //if (!in_array($deployment_id, $_SESSION['iss'][$iss]['deployment'])) {
          //  return false;
       // }
        return LTI\LTI_Deployment::new()
            ->set_deployment_id($deployment_id);
    }

    private function private_key($iss)
    {
        return $this->private_key;
        return file_get_contents(__DIR__ . $_SESSION['iss'][$iss]['private_key_file']);
    }
}

