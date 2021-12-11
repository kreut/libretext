<?php

namespace App\Http\Requests;

use App\Rules\AutoGradedDoesNotExist;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuestionRequest extends FormRequest
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
            'public' => 'required',
            'title' => 'required|string',
            'tags' => 'nullable',
            'text_question' => 'nullable',
            'a11y_question' => 'nullable',
            'answer_html' => 'nullable',
            'solution_html' => 'nullable',
            'hint' => 'nullable',
            'notes' => 'nullable',
            'license' => 'nullable',
            'license_version' => 'nullable'
        ];

        switch ($this->question_type) {
            case('assessment'):
                $rules['non_technology_text'] = !$this->technology ? 'required' : 'nullable';
                break;
            case('exposition'):
                $rules['non_technology_text'] = 'required';
        }

        if ($this->technology) {
            $rules['technology'] = ['required', Rule::in(['webwork', 'h5p', 'imathas'])];
            switch ($this->technology) {
                case('webwork'):
                    $rules['technology_id'] = ['required', 'string'];
                    break;
                case('h5p'):
                case('imathas'):
                    $rules['technology_id'] = ['required', 'integer', 'not_in:0'];
                    break;
                default:
                    $rules['technology_id'] = ['nullable'];
            }
            $question_id = $this->id ?? null;
            $rules['technology_id'][] = new AutoGradedDoesNotExist($this->technology, $question_id);

        }


        return $rules;
    }

    public function messages()
    {
        $messages = [];

        if ($this->technology === 'webwork') {
            $messages['technology_id.required'] = 'The file path field is required.';
        }
        $messages['non_technology_text.required'] = $this->question_type === 'assessment'
        ? 'Either the source field or the technology field is required.'
        : 'The source field is required.';
        return $messages;
    }
}
