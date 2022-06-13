<?php

namespace App\Http\Controllers;

use App\Exceptions\Handler;
use App\Http\Requests\StoreLTIRegistration;
use App\LtiRegistration;
use App\LtiSchool;
use App\School;
use Exception;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Snowfire\Beautymail\Beautymail;


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

    /**
     * @throws Exception
     */
    public function emailDetails(StoreLTIRegistration $request,
                                 LtiRegistration      $ltiRegistration,
                                 School               $school,
                                 LTISchool            $ltiSchool): array
    {
        $response['type'] = 'error';
        try {
            if (!DB::table('lti_pending_registrations')
                ->where('campus_id', trim($request->campus_id))
                ->first()) {
                throw new Exception("Your LTI Campus ID is not valid.  Please contact us for assistance.");
            }
            if (DB::table('lti_registrations')
                ->where('campus_id', trim($request->campus_id))
                ->first()) {
                throw new Exception("This Campus ID is already in our system.  Please contact us for assistance.");
            }
            $data = $request->validated();
            DB::beginTransaction();
            $school_id = $school->where('name', trim($request->school))->first()->id;
            $campus_id = trim($request->campus_id);
            $ltiRegistration->campus_id = trim($request->campus_id);
            $auth_server = $data['url'];
            $ltiRegistration->admin_name = $data['admin_name'];
            $ltiRegistration->admin_email = $data['admin_email'];
            $ltiRegistration->iss = "https://canvas.instructure.com";
            $ltiRegistration->auth_login_url = "$auth_server/api/lti/authorize_redirect";
            $ltiRegistration->auth_token_url = "$auth_server/login/oauth2/token";
            $ltiRegistration->auth_server = trim($auth_server);
            $ltiRegistration->client_id = trim($data['developer_key_id']);
            $ltiRegistration->key_set_url = 'https://canvas.instructure.com/api/lti/security/jwks';
            $ltiRegistration->kid = '1';
            $ltiRegistration->lti_key_id = 1;
            $ltiRegistration->active = 1;
            $ltiRegistration->save();


            DB::table('lti_pending_registrations')->where('campus_id', $campus_id)->delete();

            $ltiSchool->school_id = $school_id;
            $ltiSchool->lti_registration_id = $ltiRegistration->id;
            $ltiSchool->save();
            $to_user_email = $data['admin_email'];

            $beauty_mail = app()->make(Beautymail::class);
            $beauty_mail->send('emails.lti_registration_info', $to_user_email, function ($message)
            use ($to_user_email) {
                $message
                    ->from('adapt@noreply.libretexts.org', 'ADAPT')
                    ->to($to_user_email)
                    ->subject("Pending Refresh Question Approval");
            });
            DB::commit();
            $response['type'] = 'success';
            $response['message'] = "Thank you for your LTI integration request!  Please check your email for the next steps.";
        } catch (Exception $e) {
            DB::rollBack();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = $e->getMessage();
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
