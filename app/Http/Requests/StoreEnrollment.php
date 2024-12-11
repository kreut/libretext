<?php

namespace App\Http\Requests;

use App\Rules\IsValidStudentAccessCode;
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
        $rules['access_code'] = ['required',
            new IsValidStudentAccessCode(),
            new IsValidWhitelistedDomain($this->user()->email, $this->access_code)];
        $rules['student_id'] = 'required';
        return $rules;
    }
}
