<?php

namespace App\Http\Requests;

use App\Rules\IsValidTimeZone;
use App\Rules\IsValidWhitelistedDomain;
use Illuminate\Foundation\Http\FormRequest;

class StoreEnrollment extends FormRequest
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
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules['access_code'] = ['required', 'exists:sections,access_code', new IsValidWhitelistedDomain($this->user()->email, $this->access_code)];
        if ($this->is_lms) {
            $rules['student_id'] = 'required';
            $rules['time_zone'] = new IsValidTimeZone();
        }
        return $rules;
    }
}
