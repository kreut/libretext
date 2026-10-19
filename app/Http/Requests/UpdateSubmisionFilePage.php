<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSubmisionFilePage extends FormRequest
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
     * end_page is nullable so a page can still be assigned by an older, cached copy of the front end.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'page' => 'required|integer|min:1|not_in:0',
            'end_page' => 'nullable|integer|gte:page'
        ];
    }

    /**
     * @return array
     */
    public function messages()
    {
        return [
            'end_page.gte' => 'The end page must be the same as or after the starting page.'
        ];
    }
}
