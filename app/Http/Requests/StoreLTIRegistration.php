<?php

namespace App\Http\Requests;

use App\Rules\AreValidVanityUrls;
use App\Rules\IsValidSchoolName;
use Exception;
use Illuminate\Foundation\Http\FormRequest;

class StoreLTIRegistration extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * @throws Exception
     */
    public function rules()
    {
        switch ($this->lms) {
            case('d2l_brightspace'):
                $rules = [
                    'admin_name' => 'required|string',
                    'admin_email' => 'required|email',
                    'brightspace_keyset_url' => 'required|url',
                    'campus_id' => 'required|string',
                    'client_id' => 'required|string',
                    'issuer' => 'required|string',
                    'openid_connect_authentication_endpoint' => 'required|url',
                    'brightspace_oauth2_access_token_url' => 'required|url',
                    'school' => ['required', 'string', 'school' => new IsValidSchoolName()]
                ];
                break;
            case('canvas'):
                $rules = [
                    'admin_name' => 'required|string',
                    'admin_email' => 'required|email',
                    'url' => 'required|url',
                    'developer_key_id' => 'required|numeric',
                    'api_key' => 'required|numeric',
                    'api_secret' => 'required',
                    'campus_id' => 'required|string',
                    'school' => ['required', 'string', 'school' => new IsValidSchoolName()],
                    'vanity_urls' => new AreValidVanityUrls()
                ];
                break;
            case('moodle'):
                $rules = [
                    'admin_name' => 'required|string',
                    'admin_email' => 'required|email',
                    'client_id' => 'required|string',
                    'public_keyset_url' => 'required|url',
                    'access_token_url' => 'required|url',
                    'authentication_request_url' => 'required|url',
                    'platform_id' => 'required',
                    'campus_id' => 'required|string',
                    'school' => ['required', 'string', 'school' => new IsValidSchoolName()]
                ];
                break;
            case('blackboard'):
                $rules = ['admin_name' => 'required|string',
                    'admin_email' => 'required|email',
                    'school' => ['required', 'string', 'school' => new IsValidSchoolName()]];
                break;
            default:
                throw new Exception ("$this->lms is not a valid LMS to store the registration details.");
        }

        return $rules;
    }

    public function messages()
    {
        $messages['url.url'] = "Your URL should be of the form https://my-canvas-url.instructure.com.";
        return $messages;
    }
}
