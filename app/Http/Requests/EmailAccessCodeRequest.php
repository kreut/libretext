<?php

namespace App\Http\Requests;

use App\Rules\IsValidInstructorAccessCode;
use App\Rules\IsValidAccessCodeEmail;
use Illuminate\Foundation\Http\FormRequest;

class EmailAccessCodeRequest extends FormRequest
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
           'email' => new IsValidAccessCodeEmail()
        ];
    }
}
