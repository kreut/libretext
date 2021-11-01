<?php

namespace App\Http\Requests;

use App\Rules\IsValidListOfEmails;
use Illuminate\Foundation\Http\FormRequest;

class StoreWhiteListedInstructorEmail extends FormRequest
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
            'whitelisted_instructor_emails' => ['required', new IsValidListOfEmails()]
        ];
    }
}
