<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StoreQuestionSubjectRequest extends FormRequest
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
        $unique = Rule::unique('question_subjects','name');

        if ($this->isMethod('PATCH')) {
            $unique = $unique->ignore($this->route('question_subject')->id);
        }
        return [
            'name' => ['required', $unique]
        ];
    }
}
