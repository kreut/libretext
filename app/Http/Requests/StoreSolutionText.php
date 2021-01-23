<?php

namespace App\Http\Requests;

use App\Rules\IsValidSolutionText;
use Illuminate\Foundation\Http\FormRequest;

class StoreSolutionText extends FormRequest
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
            'solution_text' => new IsValidSolutionText($this->user()->id, $this->question_id)
        ];
    }
}
