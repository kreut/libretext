<?php

namespace App\Http\Requests;

use App\Rules\IsValidGraderAccessCode;
use App\Rules\IsValidInstructorAccessCode;
use App\Rules\IsValidTimeZone;
use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Validation\Rule;

class FinishRegistration extends FormRequest
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
        $rules = ['time_zone' => new IsValidTimeZone(),
            'registration_type' => Rule::in(['student', 'grader', 'instructor'])];
        switch ($this->registration_type) {
            case('instructor'):
                $rules['access_code'] = new IsValidInstructorAccessCode();
                break;
            case('grader'):
                $rules['access_code'] = new IsValidGraderAccessCode();
                break;
            case('student'):
                $rules['student_id'] = 'required';
                break;
        }
        return $rules;
    }
}
