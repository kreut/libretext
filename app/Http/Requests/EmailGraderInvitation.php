<?php

namespace App\Http\Requests;

use App\Rules\IsValidSections;
use Illuminate\Foundation\Http\FormRequest;

class EmailGraderInvitation extends FormRequest
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
            'email' => 'email',
            'selected_sections' => new IsValidSections($this->course_id)
        ];
    }
}
