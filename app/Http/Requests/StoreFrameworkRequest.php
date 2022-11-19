<?php

namespace App\Http\Requests;

use App\Question;
use App\Rules\IsValidFrameworkTitle;
use Illuminate\Foundation\Http\FormRequest;

;

use Illuminate\Validation\Rule;

class StoreFrameworkRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @param Question $question
     * @return array
     */
    public function rules(Question $question): array
    {
        $framework_id = $this->route()->getActionMethod() === 'update'
            ? $this->route()->parameters()['framework']->id
            : 0;
        return [
            'title' => ['required', new IsValidFrameworkTitle(request()->user()->id, $framework_id)],
            'descriptor_type' => ['required', Rule::in(['keyword','skills','taxonomy','concept', 'learning outcome', 'learning objective'])],
            'description' => 'required',
            'author' => 'required',
            'license' => ['required', Rule::in($question->getValidLicenses())],
            'source_url' => 'required'
        ];
    }

    public function messages()
    {
        $messages['descriptor_type.required'] = "The type is required.";
        return $messages;
    }
}
