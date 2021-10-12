<?php

namespace App\Http\Requests;

use App\Rules\isValidDefaultCompletionScoringType;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCompletionScoringModeRequest extends FormRequest
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
        return [
            'completion_scoring_mode' => new isValidDefaultCompletionScoringType( $this->completion_split_auto_graded_percentage)
        ];
    }
}
