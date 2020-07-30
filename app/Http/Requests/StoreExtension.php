<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExtension extends FormRequest
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
        $rules = [
            'extension_date' => 'required|date|after_or_equal:today',
            'extension_time' => 'required|date_format:H:i:00',
        ];

        return $rules;
    }
    public function messages()
    {
        return [
            'extension_date.after_or_equal' => "The date can't be in the past.",
            'extension_time.required' => 'A time is required.',
        ];
    }
}
