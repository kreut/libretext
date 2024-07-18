<?php

namespace App\Http\Requests;

use App\Rules\IsValidPeriodOfTime;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDiscussItSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
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

        return [
            "students_can_edit_comments" => ['required',Rule::in([0, 1])],
            "students_can_delete_comments" => ['required',Rule::in([0, 1])],
            "min_number_of_discussion_threads" => ['nullable','integer','min:0'],
            "min_number_of_comments" =>  ['nullable','integer', 'min:0'],
            "min_number_of_words" => ['nullable','integer','min:0'],
            'min_length_of_audio_video' => ['nullable',new IsValidPeriodOfTime()],
            'auto_grade' => ['required', Rule::in(0,1)],
        ];
    }
}
