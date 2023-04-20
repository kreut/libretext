<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportCourseRequest extends FormRequest
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
    public function rules(): array
    {
        return $this->input('shift_dates')
            ? [
                'due_date' => 'required|date',
                'due_time' => 'required|date_format:g:i A'
            ]
            : [];
    }

    /**
     * @return array
     */
    public function messages(): array
    {
        $messages = [];
        $messages['due_time.date_format'] = "12 hour format such as 9:00 AM.";
        return $messages;
    }
}
