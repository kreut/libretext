<?php

namespace App\Http\Controllers;

use App\Exceptions\Handler;
use App\Http\Requests\StoreLTIRegistration;
use App\LtiRegistration;
use Exception;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;


class LtiRegistrationController extends Controller
{
    public function index(LtiRegistration $ltiRegistration)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('index', $ltiRegistration);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $response['type'] = 'success';
            $response['lti_registrations'] = $ltiRegistration->all();

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were unable to get the LTI registrations.  Please try again.";
        }
        return $response;

    }

    public function emailDetails(StoreLTIRegistration $request)
    {
        $response['type'] = 'error';
        $data = $request->validated();
        try {
            $text = <<<EOT
URL: {$data['url']} ---
Campus Id: $request->campus_id ---
Schools: {$request->schools} ---
Developer Key Id: {$data['developer_key_id']} ---
Admin Name: {$data['admin_name']} ---
Admin Email: {$data['admin_email']}
EOT;

            Mail::to('adapt@libretexts.org')
                ->send(new \App\Mail\Email("LTI integration request", $text, $data['admin_email'], $data['admin_name']));

            $response['type'] = 'success';
            $response['message'] = "Thank you for your LTI integration request!  We'll be in touch with information about the next steps.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error sending the email.  Please try again.";
        }
        return $response;

    }

    public function store(StoreLTIRegistration $request, LtiRegistration $ltiRegistration)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('store', $ltiRegistration);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        $response['type'] = 'error';
        $data = $request->validated();
        $url = $data['url'];
        $iss = "https://canvas.instructure.com";
        try {
            $ltiRegistration->campus_id = $data['campus_id'];
            $ltiRegistration->admin_name = $data['admin_name'];
            $ltiRegistration->admin_email = $data['admin_email'];
            $ltiRegistration->iss = $iss;
            $ltiRegistration->auth_login_url = "$url/api/lti/authorize_redirect";
            $ltiRegistration->auth_token_url = "$url/login/oauth2/token";
            $ltiRegistration->auth_server = $url;
            $ltiRegistration->client_id = $data['developer_key_id'];
            $ltiRegistration->key_set_url = "$iss/api/lti/security/jwks";
            $ltiRegistration->kid = 1;
            $ltiRegistration->lti_key_id = 1;
            $ltiRegistration->active = 0;
            $ltiRegistration->save();

            $response['type'] = 'success';
            $response['message'] = "The registration has been saved.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error saving the registration.  Please try again.";
        }
        return $response;

    }

    public function active(LtiRegistration $ltiRegistration)
    {
        {

            $response['type'] = 'error';
            $authorized = Gate::inspect('active', $ltiRegistration);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }

            $response['type'] = 'error';

            try {
                $active = $ltiRegistration->active;
                $ltiRegistration->active = !$active;
                $ltiRegistration->save();

                $verb = !$active ? ' ' : " not ";
                $response['type'] = !$active ? 'success' : 'info';
                $response['message'] = "The registration is{$verb}active.";
            } catch (Exception $e) {
                $h = new Handler(app());
                $h->report($e);
                $response['message'] = "There was an error toggling the active state.  Please try again.";
            }
            return $response;
        }
    }
}
