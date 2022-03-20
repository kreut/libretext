<?php

namespace App\Http\Requests;

use App\Traits\LearningTreeSuccessRubricRules;
use Illuminate\Foundation\Http\FormRequest;

class LearningTreeRubric extends FormRequest
{
    use LearningTreeSuccessRubricRules;
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
        return $this->learningTreeSuccessRubricRules($this);
    }
}
