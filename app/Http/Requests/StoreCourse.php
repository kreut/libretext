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
        $rules = [
            'name' => ['required',
                'max:255'],
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date'
        ];
        if ($this->route('course')) {
            array_push($rules['name'], Rule::unique('courses', 'name')->ignore($this->route('course')->id));
        }
        return $rules;
    }
}
