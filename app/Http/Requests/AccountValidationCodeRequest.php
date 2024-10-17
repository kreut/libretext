<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AccountValidationCodeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'validation_code'=>['required','exists:link_to_account_validation_codes,validation_code']
        ];
    }
}
