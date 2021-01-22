<?php

namespace App\Http\Requests;

use App\Rules\IsValidTextSolution;
use Illuminate\Foundation\Http\FormRequest;

class StoreTextSolution extends FormRequest
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
            'text_solution' => new IsValidTextSolution($this->user()->id, $this->question_id)
        ];
    }
}
