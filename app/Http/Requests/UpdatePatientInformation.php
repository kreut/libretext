<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePatientInformation extends FormRequest
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
        return [
            'name' => 'required',
            'code_status' => ['required', Rule::in('full_code', 'dnr')],
            'gender' => 'required',
            'allergies' => 'required',
            'age' => 'required',
            'weight' => 'required',
            'weight_units' => ['required', Rule::in('lbs', 'kilos')],
            'dob' => 'required',
            'bmi' => 'required'
        ];
    }
}
