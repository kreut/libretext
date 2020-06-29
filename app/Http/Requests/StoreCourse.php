<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCourse extends FormRequest
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
            'name' => ['required',
            'max:255',
            Rule::unique('courses', 'name')->ignore($this->route('course')->id)
            ],
            'start_date' => 'required|date|after_or_equal:' . date('Y-m-d'),
            'end_date' => 'required|date|after:start_date'
        ];
    }
}
