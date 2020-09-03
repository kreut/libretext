<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssignment extends FormRequest
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
            'available_from_date' => 'required|date',
            'due_date' => 'required|date|after:available_from_date',
            'available_from_time' => 'required|date_format:H:i:00',
            'due_time' => 'required|date_format:H:i:00',
            'default_points_per_question' => 'required|integer|min:0|max:100',
            'submission_files' => Rule::in(['q','a',0]),
        ];
        return $rules;
    }

    public function messages()
    {
        return [
            'available_from_time.required' => 'A time is required.',
            'due_time.required' => 'A time is required.'
        ];
    }
}
