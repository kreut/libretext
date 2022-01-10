<?php

namespace App\Http\Requests;

use App\Rules\AutoGradedDoesNotExist;
use App\Rules\IsValidSavedQuestionsFolder;
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
            'question_type' => Rule::in('assessment', 'exposition'),
            'public' => 'required',
            'folder_id' => ['required', Rule::exists('saved_questions_folders', 'id')
                ->where('user_id', $this->user()->id)
                ->where('type', 'my_questions')],
            'title' => 'required|string',
            'author' => 'nullable',
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
                if ($this->technology === 'text') {
                    $rules['non_technology_text'] = 'required';
                    $rules['technology'] = 'nullable';
                    $rules['technology_id'] = 'nullable';
                } else {
                    $rules['non_technology_text'] = 'nullable';
                    $rules['technology'] = ['required', Rule::in(['text', 'webwork', 'h5p', 'imathas'])];
                    switch ($this->technology) {
                        case('webwork'):
                            $rules['technology_id'] = ['required', 'string'];
                            break;
                        case('h5p'):
                        case('imathas'):
                            $rules['technology_id'] = ['required', 'integer', 'not_in:0'];
                            break;
                        case('text'):
                            $rules['technology_id'] = ['nullable'];
                    }
                    $question_id = $this->id ?? null;
                    $rules['technology_id'][] = new AutoGradedDoesNotExist($this->technology, $question_id);
                }
                break;
            case('exposition'):
                $rules['non_technology_text'] = 'required';
                $rules['technology'] = ['required', Rule::in(['text'])];
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
        $messages['folder_id.required'] = "The folder is required.";
        $messages['folder_id.exists'] = "That is not one of your My Questions folders.";
        return $messages;
    }
}
