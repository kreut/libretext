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
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'max:255'],
            'term' => 'required',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'school' => new IsValidSchoolName(),
            'alpha' => Rule::in([0, 1]),
            'public' => Rule::in([0, 1]),
            'anonymous_users' => Rule::in([0, 1]),
            'lms' => Rule::in([0, 1]),
            'textbook_url' => 'nullable|url'
        ];
        if ($this->route()->getActionMethod() === 'store') {
            $rules['crn'] = 'required';
            $rules['section'] = ['required', 'max:255', 'regex:/^((?!---).)*$/'];
        }
        return $rules;

    }

    public function messages(): array
    {
        $messages['section.regex'] = "The section name can't contain '---'.";
        $messages['textbook_url.url'] = "The URL should be of the form https://my-textbook-url.com";
        return $messages;
    }
}
