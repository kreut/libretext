<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InviteStudentRequest extends FormRequest
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
        if (in_array($this->invitation_type, ['email_list', 'student_from_roster_invitation'])) {
            return [];
        }

        return [
            'course_id' => 'required',
            'section_id' => 'required',
            'last_name' => 'required',
            'first_name' => 'required',
            'email' => ['required', 'email']
        ];
    }
}
