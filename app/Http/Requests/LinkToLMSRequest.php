<?php

namespace App\Http\Requests;

use App\Rules\IsValidLMSCourse;
use Illuminate\Foundation\Http\FormRequest;

class LinkToLMSRequest extends FormRequest
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
        $course_id = $this->route('course')->id;
        return [
            'lms_course_id' => ['required', new IsValidLMSCourse($course_id)]
        ];
    }
}
