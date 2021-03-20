<?php

namespace App\Http\Requests;

use App\Rules\IsValidSection;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEnrollment extends FormRequest
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
            'section_id' => new IsValidSection($this->course)
        ];
    }
}
