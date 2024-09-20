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
        $rules = [
            'response_modes' => ['required'],
            "students_can_edit_comments" => ['required', Rule::in([0, 1])],
            "students_can_delete_comments" => ['required', Rule::in([0, 1])],
            "min_number_of_discussion_threads" => ['required', 'integer', 'min:1'],
            "min_number_of_comments" => ['required', 'integer', 'min:1'],
            "min_number_of_words" => ['required', 'integer', 'min:1'],
            'min_length_of_audio_video' => ['required', new IsValidPeriodOfTime()],
            'auto_grade' => ['required', Rule::in(0, 1)]];

        if (in_array('audio', $this->response_modes) || in_array('video', $this->response_modes)) {
            $language_codes = [
                'en', 'fr', 'es', 'de', 'zh', 'ar', 'it', 'ru', 'pt', 'ja', 'hi', 'bn', 'pa'
            ];
            $rules['language'] = ['required', Rule::in($language_codes)];
        }
        return $rules;
    }

    public function messages()
    {
        $messages['response_modes.required'] = "At least one method of responding is required.";
        return $messages;

    }
}
