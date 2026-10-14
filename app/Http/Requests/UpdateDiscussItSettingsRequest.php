<?php

namespace App\Http\Requests;

use App\Helpers\Helper;
use App\DiscussionCommentAttachment;
use App\Rules\IsValidMinNumberForDiscussIt;
use App\Rules\IsValidNumberOfDiscussItGroups;
use App\Rules\IsValidPeriodOfTime;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
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
     * Clients from before new threads/replies could be turned off don't send them; both default to allowed.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        foreach (['students_can_start_threads', 'students_can_reply_to_threads'] as $key) {
            if (!$this->has($key)) {
                $this->merge([$key => '1']);
            }
        }
        //"file" was briefly a response mode before files became attachments; settings saved then still have it
        if (is_array($this->response_modes)) {
            $this->merge(['response_modes' => array_values(array_diff($this->response_modes, ['file']))]);
        }
        if (!$this->has('students_must_post_before_viewing')) {
            $this->merge(['students_must_post_before_viewing' => '0']);
        }
        //attaching files is off unless turned on; when on, every type is allowed unless the instructor narrows it down
        if (!$this->has('students_can_attach_files')) {
            $this->merge(['students_can_attach_files' => '0']);
        }
        if (!$this->has('allowed_file_types')) {
            $this->merge(['allowed_file_types' => array_keys(DiscussionCommentAttachment::fileTypes())]);
        }
        if (!$this->has('min_number_of_attached_files') || $this->min_number_of_attached_files === null || $this->min_number_of_attached_files === '') {
            $this->merge(['min_number_of_attached_files' => '0']);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(Request $request): array
    {
        $assignment_id = $request->route('assignment')->id;
        $question_id = $request->route('question')->id;
        $response_modes = $this->response_modes ?: [];
        $rules = [
            'response_modes' => ['required', 'array'],
            'response_modes.*' => [Rule::in(['text', 'audio', 'video'])],
            'students_can_attach_files' => ['required', Rule::in([0, 1])],
            'allowed_file_types' => +$this->students_can_attach_files === 1 ? ['required', 'array', 'min:1'] : ['array'],
            'allowed_file_types.*' => [Rule::in(array_keys(DiscussionCommentAttachment::fileTypes()))],
            'min_number_of_attached_files' => ['required', 'integer', 'min:0', 'max:1000',
                function ($attribute, $value, $fail) {
                    if (+$value > 0 && +$this->students_can_attach_files !== 1) {
                        $fail('To require attached files, students must be able to attach files.');
                    }
                }],
            'students_must_post_before_viewing' => ['required', Rule::in([0, 1]),
                function ($attribute, $value, $fail) {
                    $error = Helper::discussItMustPostBeforeViewingError($this->all());
                    if ($error) {
                        $fail($error);
                    }
                }],
            'number_of_groups' => new IsValidNumberOfDiscussItGroups($assignment_id, $question_id),
            "students_can_edit_comments" => ['required', Rule::in([0, 1])],
            "students_can_delete_comments" => ['required', Rule::in([0, 1])],
            "students_can_start_threads" => ['required', Rule::in([0, 1])],
            "students_can_reply_to_threads" => ['required', Rule::in([0, 1])],
            "apply_to_assignment_ids" => ['sometimes', 'array'],
            "apply_to_assignment_ids.*" => ['integer'],
            "confirm_participation_warnings" => ['sometimes', 'boolean'],
            "min_number_of_initiated_discussion_threads" => ['required', 'integer', 'min:0'],
            "min_number_of_comments" => ['integer', 'min:0'],
            "min_number_of_replies" => ['required', new IsValidMinNumberForDiscussIt()],
            "min_number_of_initiate_or_reply_in_threads" => ['required', 'integer', 'min:0'],
            "min_number_of_words" => in_array('text', $response_modes) ? ['required', 'integer', 'min:1'] : '',
            'min_length_of_audio_video' => in_array('audio', $response_modes) || in_array('video', $response_modes) ? ['required', new IsValidPeriodOfTime()] : '',
            'auto_grade' => ['required', Rule::in(0, 1)],
            'completion_criteria' => ['required', Rule::in(0, 1)]];

        if (is_array($response_modes) && (in_array('audio', $response_modes) || in_array('video', $response_modes))) {
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
        $messages['allowed_file_types.required'] = "Choose at least one type of file students can upload.";
        $messages['allowed_file_types.min'] = "Choose at least one type of file students can upload.";
        return $messages;

    }
}
