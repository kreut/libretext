<?php

namespace App\Http\Controllers;

use App\CanvasVanityUrl;
use App\Exceptions\Handler;
use App\Http\Requests\StoreLTIRegistration;
use App\Http\Requests\UpdateAPIKeyRequest;
use App\LtiRegistration;
use App\LtiSchool;
use App\School;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Snowfire\Beautymail\Beautymail;
use Telegram\Bot\Laravel\Facades\Telegram;


class LtiRegistrationController extends Controller
{

    /**
     * @param UpdateAPIKeyRequest $request
     * @param LtiRegistration $ltiRegistration
     * @return array
     * @throws Exception
     */
    public function updateAPIKey(UpdateAPIKeyRequest $request, LtiRegistration $ltiRegistration): array
    {
        $response['type'] = 'error';
        try {
            DB::beginTransaction();
            $lti_registration = $ltiRegistration->where('campus_id', $request->campus_id)->first();
            if (!$lti_registration) {
                $response['message'] = "That is not a valid campus ID for the Canvas API form.";
                return $response;
            }
            if ($lti_registration->api_key || $lti_registration->api_secret) {
                $response['message'] = "The key and secret are already in our system.  If you need to update them, please contact ADAPT support.";
                return $response;
            }
            $data = $request->validated();
            $lti_registration->api_key = $data['api_key'];
            $lti_registration->api_secret = $data['api_secret'];
            $lti_registration->save();
            Telegram::sendMessage([
                'chat_id' => config('myconfig.telegram_channel_id'),
                'parse_mode' => 'HTML',
                'text' => "$request->campus_id just added API support."
            ]);
            $response['type'] = 'success';
            $response['message'] = 'The key has been saved and your faculty have access to the API.';
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were unable to save your API key.  Please try again.";
        }
        return $response;
    }

    /**
     * @param string $type
     * @param string $campusId
     * @return array
     * @throws Exception
     */
    public function isValidCampusId(string $type, string $campusId): array
    {

        $response['type'] = 'error';
        try {
            if (!in_array($type, ['api-check', 'pending'])) {
                $response['message'] = "$type is not a valid parameter for isValidCampusId";
                return $response;
            }
            $table = $type === 'pending' ? 'lti_pending_registrations' : 'lti_registrations';
            $response['is_valid_campus_id'] = (bool)DB::table($table)
                ->where('campus_id', $campusId)
                ->first();
            $response['type'] = 'success';
            return $response;
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were unable to validate the LTI campus ID.  Please try again.";
        }
        return $response;

    }

    /**
     * @param LtiRegistration $ltiRegistration
     * @return array
     * @throws Exception
     */
    public function index(LtiRegistration $ltiRegistration): array
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('index', $ltiRegistration);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $response['type'] = 'success';
            $lti_registrations = $ltiRegistration->all();
            foreach ($lti_registrations as $lti_registration) {
                $lti_registration->api = $lti_registration->api_key !== null ? 'Yes' : 'No';
            }
            $response['lti_registrations'] = $lti_registrations;
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
            $auth_server = rtrim(trim($data['url'], '/'));
            $ltiRegistration->admin_name = $data['admin_name'];
            $ltiRegistration->admin_email = $data['admin_email'];
            $ltiRegistration->iss = "https://canvas.instructure.com";
            $ltiRegistration->auth_login_url = "$auth_server/api/lti/authorize_redirect";
            $ltiRegistration->auth_token_url = "$auth_server/login/oauth2/token";
            $ltiRegistration->auth_server = $auth_server;
            $ltiRegistration->client_id = trim($data['developer_key_id']);
            $ltiRegistration->api_key = $data['api_key'];
            $ltiRegistration->api_secret = $data['api_secret'];
            $ltiRegistration->key_set_url = 'https://canvas.instructure.com/api/lti/security/jwks';
            $ltiRegistration->kid = '1';
            $ltiRegistration->lti_key_id = 1;
            $ltiRegistration->active = 1;
            $ltiRegistration->save();
            if (isset($data['vanity_urls'])) {
                $vanity_urls = explode(',', $data['vanity_urls']);
                foreach ($vanity_urls as $vanity_url) {
                    $vanity_url = rtrim(trim($vanity_url), '/');
                    $parse = parse_url($vanity_url);
                    $vanity_url = $parse['host'];
                    $canvasVanityUrl = new CanvasVanityUrl();
                    if (!$canvasVanityUrl->where('vanity_url', $vanity_url)->first()) {
                        $canvasVanityUrl->vanity_url = $vanity_url;
                        $canvasVanityUrl->lti_registration_id = $ltiRegistration->id;
                        $canvasVanityUrl->save();
                    }
                }
            }

            DB::table('lti_pending_registrations')->where('campus_id', $campus_id)->delete();

            $ltiSchool->school_id = $school_id;
            $ltiSchool->lti_registration_id = $ltiRegistration->id;
            $ltiSchool->save();
            $to_user_email = $data['admin_email'];

            $beauty_mail = app()->make(Beautymail::class);
            $beauty_mail->send('emails.lti_registration_info', [], function ($message)
            use ($to_user_email) {
                $message
                    ->from('adapt@noreply.libretexts.org', 'ADAPT')
                    ->to($to_user_email)
                    ->subject("Complete LTI Registration");
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
