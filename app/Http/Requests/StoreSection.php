<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSection extends FormRequest
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
        $course_id = $this->course ? $this->course->id : 0;
        return [
            'name' => ['required',
                'max:255',
                'unique:sections,name,NULL,id,course_id,' . $course_id],
            'crn' => 'required'
        ];
    }
}
