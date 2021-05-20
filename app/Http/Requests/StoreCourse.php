<?php

namespace App\Http\Requests;

use App\Rules\IsValidSchoolName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCourse extends FormRequest
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
       return  [
            'name' => ['required', 'max:255'],
            'section' => ['required', 'max:255','regex:/^((?!---).)*$/'],
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'school' =>  new IsValidSchoolName(),
            'public' => Rule::in([0,1])
        ];

    }

    public function messages() {
        return ['section.regex' => "The section name can't contain '---'."];
    }
}
