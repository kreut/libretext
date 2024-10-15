<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IsValidEmailUpdateRequest extends FormRequest
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
            'email' => [
                'required',        // Email is required
                'email',           // Must be a valid email address
                'unique:users,email'  // Email must be unique in the users table
            ],
        ];
    }
}
