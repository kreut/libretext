<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GradingRequest extends FormRequest
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
        $rules['text_feedback_editor']= Rule::in(['rich','plain']);
        if ($this->question_submission_score !== null){
            $rules['question_submission_score'] = 'numeric|gte:0';
         }
        if ($this->file_submission_score !== null){
            $rules['file_submission_score'] = 'numeric|gte:0';
        }
        if ($this->applied_late_penalty !== null){
            $rules['applied_late_penalty'] = 'numeric|gte:0|lte:100';

        }
       return $rules;

    }
}
