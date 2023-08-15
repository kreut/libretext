<?php

namespace App\Http\Requests;

use App\Rules\IsValidSubmissionScoreOverride;
use Illuminate\Foundation\Http\FormRequest;

class SubmissionScoreOverrideRequest extends FormRequest
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
        $assignment_id = $this->input('assignment_id');
        $question_id = $this->input('question_id');
        return [
            'score' => ['required', 'numeric', 'min:0', new IsValidSubmissionScoreOverride($assignment_id, $question_id)]
        ];
    }
}
