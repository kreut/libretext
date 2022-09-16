<?php

namespace App\Http\Requests;

use App\Rules\IsValidTesterEmail;
use Illuminate\Foundation\Http\FormRequest;

class StoreTester extends FormRequest
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
            'email' => ['required', new IsValidTesterEmail($this->course_id)]
        ];
    }
}
