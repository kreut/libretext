<?php

namespace App\Http\Requests;

use App\Assignment;
use App\Rules\EmailsAreFromCourse;
use Illuminate\Foundation\Http\FormRequest;

class EmailStudentsWithSubmissionsRequest extends FormRequest
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
        $assignment = Assignment::find($this->assignment_id);
        $course_id = $assignment->course->id;
        return [
            'message' => 'required',
            'emails' => ['required', new EmailsAreFromCourse($course_id)]
        ];
    }
}
