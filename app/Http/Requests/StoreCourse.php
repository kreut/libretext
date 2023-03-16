<?php

namespace App\Http\Requests;

use App\Rules\Formative;
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
        if ($this->user()->role === 5) {
            $rules['name'] = ['required', 'max:255'];
            $rules['public'] = Rule::in([0, 1]);
            return $rules;
        } else {
            $rules = [
                'name' => ['required', 'max:255'],
                'term' => 'required',
                'school' => new IsValidSchoolName(),
                'alpha' => Rule::in([0, 1]),
                'public' => Rule::in([0, 1]),
                'anonymous_users' => Rule::in([0, 1]),
                'formative' => Rule::in([0, 1]),
                'lms' => Rule::in([0, 1]),
                'textbook_url' => 'nullable|url'
            ];
            if (!$this->formative) {
                $rules['start_date'] = 'required|date';
                $rules['end_date'] = 'required|date|after:start_date';
                if ($this->user()->role === 2) {
                    $rules['whitelisted_domains'] = ['required', 'array'];
                }
            }
            if ($this->route()->getActionMethod() === 'update') {
                $rules['formative'] = [Rule::in([0, 1]), new Formative($this->route('course'))];

            }

            if ($this->route()->getActionMethod() === 'store' && !$this->formative) {
                $rules['crn'] = 'required';
                $rules['section'] = ['required', 'max:255', 'regex:/^((?!---).)*$/'];
            }
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
