<?php

namespace App\Http\Requests;

use App\Rules\IsValidPeriodOfTime;
use App\Rules\IsValidPeriodOfTimeForGraderNotifications;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGraderNotification extends FormRequest
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
        return ($this->num_reminders_per_week
            || $this->copy_grading_reminder_to_head_grader
            || $this->copy_grading_reminder_to_instructor)
            ? ['num_reminders_per_week' => Rule::in(0,1,2,7)]
            : [];

    }

}
