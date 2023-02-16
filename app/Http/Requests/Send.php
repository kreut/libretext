<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class Send extends FormRequest
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

        $rules = [
            'subject' => 'required',
            'text' => 'required'
        ];
        if ($this->type !== 'contact_grader'){
            $rules['email'] = 'email';
            $rules['name'] = 'required';
        }
        if ($this->subject === 'Request Instructor Access Code') {
            $rules['school'] = 'required';
        }
        return $rules;

    }
}
