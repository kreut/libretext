<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \IMSGlobal\LTI;
use App\Custom\LTIDatabase;
use GuzzleHttp\Client;

class LTIController extends Controller
{
    public function initiateLoginRequest(Request $request)
    {
        $launch_url = "https://dev.adapt.libretexts.org/api/lti/redirect-uri";
        $private_key = 'E6OFY4PGAZZuZVqcGnLA84SCHHYpxxa1MbpfwsnQGTyld0mjCNQ5FYzvHxFdbp9O';
       /* LTI\LTI_OIDC_Login::new(new LTIDatabase($request, $private_key))
            ->do_oidc_login_redirect($launch_url, $request->all())
            ->do_redirect();
        dd('sdfsfd');*/
            $iss = $request->iss;
        $login_hint = $request->login_hint;
        $target_link_uri = $request->target_link_uri;
        $lti_message_hint = $request->lti_message_hint;
        $state = uniqid('nonce');
$client_id = '10000000000002';
       // https://canvas.instructure.com/api/lti/authorize_redirect
      //  https://dev-canvas.libretexts.org/api/lti/authorize_redirect

        $state = uniqid('nonce');
        $redirect_uri = 'https://dev.adapt.libretexts.org/api/lti/redirect-uri';
        //header("Location: https://dev-canvas.libretexts.org/api/lti/authorize",true,302);exit;
        //$iss = "https://dev-canvas.libretexts.org";
$client = new Client();
$client->request('GET',"https://dev-canvas.libretexts.org/api/lti/authorize?scope=openid&prompt=none&response_mode=form_post&nonce=1234&response_type=id_token&redirect_uri=$redirect_uri&client_id=$client_id&iss=$iss&login_hint=$login_hint&target_link_uri=$target_link_uri&lti_message_hint=$lti_message_hint&state=$state");
exit;

/*
        LTI\LTI_OIDC_Login::new(new LTIDatabase($request, $private_key))
            ->do_oidc_login_redirect($launch_url, $request->all())
            ->do_redirect();*/
    }

    public function authenticationResponse(Request $request){
       dd($request->all());

    }

    public function finalTarget(Request $request){
        dd($request->all());

    }


    /*
    To complete authentication, tools are expected to send back an authentication request to an "OIDC Authorization end-point". This can be a GET or POST. For cloud-hosted Canvas, regardless of the domain used by the client, the endpoint is always:

    https://canvas.instructure.com/api/lti/authorize_redirect (if launched from a production environment)
    https://canvas.beta.instructure.com/api/lti/authorize_redirect (if launched from a beta environment)
    https://canvas.test.instructure.com/api/lti/authorize_redirect (if launched from a test environment)
    Among the required variables the request should include:

    a redirect_uri, which must match at least one configured on the developer key.
    a client_id that matches the developer key. This must be registered in the tool before the launch occurs.
    the same login_hint that Canvas sent in Step 1.
    a state parameter the tool will use to validate the request in Step 4.*/


//'client_id' =>10000000000002
    /**array:4 [▼
     * "iss" => "https://canvas.instructure.com"
     * "login_hint" => "f326d6a8a55f30f47b2480586f97991ab9e602bb"
     * "target_link_uri" => "https://dev.adapt.libretexts.org/api/lti/target-link-uri"
     * "lti_message_hint" => "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ2ZXJpZmllciI6bnVsbCwiY2FudmFzX2RvbWFpbiI6ImxvY2FsaG9zdCIsImNvbnRleHRfdHlwZSI6IkNvdXJzZSIsImNvbnRleHRfaWQiOjEwMDAwMDAwMDAwMDAxLCJleHAiOjE2MDU4MTgyMjl9.Ln6VDW8sY23mYh_AexlEBon3YGfQ07kmv6SAu8pN1S8 ◀"
     * ]*/

}
