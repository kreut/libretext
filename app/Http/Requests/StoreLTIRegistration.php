<?php

namespace App\Http\Requests;

use App\Rules\AreValidVanityUrls;
use App\Rules\IsValidSchoolName;
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

    public function rules()
    {
        return [
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
    }

    public function messages()
    {
        $messages['url.url'] = "Your URL should be of the form https://my-canvas-url.instructure.com.";
        return $messages;
    }
}
