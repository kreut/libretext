<?php

namespace App\Http\Requests;

use App\Rules\atLeastOneFillInTheBlank;
use App\Rules\atLeastOneSelectChoice;
use App\Rules\atLeastTwoMatches;
use App\Rules\atLeastTwoResponses;
use App\Rules\AutoGradedDoesNotExist;
use App\Rules\correctResponseRequired;
use App\Rules\IsValidCourseAssignmentTopic;
use App\Rules\IsValidMatchingPrompt;
use App\Rules\IsValidNumericalPrompt;
use App\Rules\IsValidQtiPrompt;
use App\Rules\IsValidSelectChoice;
use App\Rules\nonRepeatingMatchingTerms;
use App\Rules\nonRepeatingSimpleChoice;
use App\Rules\nonRepeatingTermsToMatch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class StoreQuestionRequest extends FormRequest
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
            'question_type' => Rule::in('assessment', 'exposition'),
            'public' => 'required',
            'title' => 'required|string',
            'author' => 'nullable',
            'tags' => 'nullable',
            'text_question' => 'nullable',
            'a11y_technology' => 'nullable',
            'a11y_technology_id' => 'nullable',
            'answer_html' => 'nullable',
            'solution_html' => 'nullable',
            'hint' => 'nullable',
            'notes' => 'nullable',
            'license' => 'nullable',
            'license_version' => 'nullable'
        ];
        $rules['folder_id'] = ['required'];
        if ($this->course_id || $this->assignment || $this->topic) {
            $rules['folder_id'][] = new IsValidCourseAssignmentTopic($this->course_id, $this->assignment, $this->topic);
        }
        if ($this->learning_outcome_id) {
            $rules['learning_outcome_id'] = 'exists:learning_outcomes,id';
        }
        switch ($this->question_type) {
            case('assessment'):
                if ($this->technology === 'text') {
                    $rules['non_technology_text'] = 'required';
                    $rules['technology'] = 'nullable';
                    $rules['technology_id'] = 'nullable';
                } else {
                    $rules['non_technology_text'] = 'nullable';
                    $rules['technology'] = ['required', Rule::in(['text', 'webwork', 'h5p', 'imathas', 'qti'])];
                    $rules['a11y_technology'] = [Rule::in([null, 'webwork', 'h5p', 'imathas'])];
                    switch ($this->technology) {
                        case('webwork'):
                            if ($this->create_auto_graded_code === 'webwork') {
                                $rules['webwork_code'] = ['required', 'string'];
                            } else {
                                $rules['technology_id'] = ['required', 'string'];
                            }
                            if ($this->a11y_technology) {
                                $rules['technology_id'] = ['required', 'string'];
                            }
                            break;
                        case('h5p'):
                        case('imathas'):
                            $rules['technology_id'] = ['required', 'integer', 'not_in:0'];
                            if ($this->a11y_technology) {
                                $rules['a11y_technology_id'] = ['required', 'integer', 'not_in:0'];
                            }
                            break;
                        case('qti'):
                            $qti_array = json_decode($this->qti_json, true);
                            switch ($qti_array['questionType']) {
                                case('numerical'):
                                    $rules['qti_prompt'] = ['required', new IsValidNumericalPrompt($this->qti_json, $this->route('question'))];
                                    $rules['correct_response'] = 'required|numeric';
                                    $rules['margin_of_error'] = 'required|numeric|min:0';
                                    break;
                                case('matching'):
                                    $rules['qti_prompt'] = ['required', new IsValidMatchingPrompt($qti_array['questionType'], $this->qti_json, $this->route('question'))];
                                    foreach ($this->all() as $key => $value) {
                                        if (strpos($key, 'qti_matching_') !== false) {
                                            $rules[$key] = ['required'];
                                        }
                                    }
                                    $rules['qti_matching_term_to_match_0'][] = new nonRepeatingTermsToMatch($qti_array);
                                    $rules['qti_matching_term_to_match_0'][] = new nonRepeatingMatchingTerms($qti_array);
                                    $rules['qti_matching_term_to_match_0'][] = new atLeastTwoMatches($qti_array);
                                    break;
                                case('multiple_answers'):
                                case('multiple_choice'):
                                case('true_false'):
                                    $rules['qti_prompt'] = ['required', new IsValidQtiPrompt($this->qti_json, $this->route('question'))];
                                    $qti_simple_choices = [];
                                    foreach ($this->all() as $key => $value) {
                                        if (strpos($key, 'qti_simple_choice_') !== false) {
                                            $qti_simple_choices[] = $value;
                                            $rules[$key] = ['required'];
                                        }
                                    }
                                    $rules['qti_simple_choice_0'][] = new nonRepeatingSimpleChoice($qti_simple_choices);
                                    $rules['qti_simple_choice_0'][] = new correctResponseRequired($this->qti_json);
                                    $rules['qti_simple_choice_0'][] = new atLeastTwoResponses($qti_simple_choices);
                                    break;
                                case ('select_choice'):
                                    foreach ($this->all() as $key => $value) {
                                        if (strpos($key, 'qti_select_choice_') !== false) {
                                            $identifier = str_replace('qti_select_choice_', '', $key);
                                            $rules[$key] = new IsValidSelectChoice($qti_array, $identifier);
                                        }
                                    }
                                    $rules['qti_item_body'] = ['required', new atLeastOneSelectChoice($qti_array)];
                                    break;
                                case('fill_in_the_blank'):
                                    $rules['qti_item_body'] = ['required', new atLeastOneFillInTheBlank($qti_array)];
                                    break;
                            }
                            break;
                        case('text'):
                            $rules['technology_id'] = ['nullable'];
                    }
                    $question_id = $this->id ?? null;
                    if (!$this->bulk_upload_into_assignment) {
                        if ($this->technology !== 'qti') {
                            $rules['technology_id'][] = new AutoGradedDoesNotExist($this->technology, $this->webwork_code, $question_id);
                        }
                    }
                }
                break;
            case('exposition'):
                $rules['non_technology_text'] = 'required';
                $rules['technology'] = ['required', Rule::in(['text'])];
        }


        return $rules;
    }

    public
    function messages()
    {
        $messages = [];

        if ($this->technology === 'webwork') {
            $messages['technology_id.required'] = 'The file path field is required.';
            $messages['webwork_code.required'] = 'WeBWork code is required.';
        }
        $messages['non_technology_text.required'] = $this->question_type === 'assessment'
            ? 'Either the header HTML field or the technology field is required.'
            : 'The header HTML field is required.';
        $messages['folder_id.required'] = "The folder is required.";

        foreach ($this->all() as $key => $value) {
            if (strpos($key, 'qti_simple_choice_') !== false) {
                $index = str_replace("qti_simple_choice_", '', $key) + 1;
                $messages["$key.required"] = "Response $index is missing an entry.";
            }
        }
        foreach ($this->all() as $key => $value) {
            if (strpos($key, 'qti_matching_term_to_match_') !== false) {
                $index = str_replace("qti_matching_term_to_match_", '', $key) + 1;
                $messages["$key.required"] = "The term to match from Matching $index is required.";
            }
            if (strpos($key, 'qti_matching_matching_term_') !== false) {
                $index = str_replace("qti_matching_matching_term_", '', $key) + 1;
                $messages["$key.required"] = "The matching term from Matching $index is required.";
            }
            if (strpos($key, 'qti_matching_distractor_') !== false) {
                $index = str_replace("qti_matching_distractor_", '', $key) + 1;
                $messages["$key.required"] = "Distractor $index is required.";
            }


        }

        $messages['qti_prompt.required'] = "A prompt is required.";
        $messages['qti_item_body.required'] = "The question text is required.";
        return $messages;
    }
}
