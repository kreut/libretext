<?php

namespace App\Http\Requests;


use App\Assignment;
use App\Course;
use App\Rules\HasNoRandomizedAssignmentQuestions;
use App\Rules\IsNotClickerAssessment;
use App\Rules\IsNotOpenOrNoSubmissions;
use App\Rules\isValidDefaultCompletionScoringType;
use App\Rules\IsValidNumberOfAllowedAttemptsPenalty;
use App\Rules\IsValidPeriodOfTime;
use App\Rules\IsADateLaterThan;
use App\Rules\IsValidSubmissionCountPercentDecrease;
use App\Rules\IsValidLatePolicyForCompletedScoringType;
use App\Rules\IsValidAssesmentTypeForScoringType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class StoreAssignment extends FormRequest
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
            'name' => ['required', 'max:255'],
            'source' => Rule::in(['a', 'x']),
            'scoring_type' => Rule::in(['c', 'p']),
            'late_policy' => Rule::in(['not accepted', 'marked late', 'deduction']),
            'assignment_group_id' => 'required|exists:assignment_groups,id',
            'include_in_weighted_average' => Rule::in([0, 1]),
            'instructions' => 'max:10000',
            'default_open_ended_submission_type' => Rule::in(['file', 'rich text', 'plain text', 'audio', 0]),
            'notifications' => Rule::in([0, 1]),
        ];


        if ($this->file_upload_mode === 'compiled_pdf') {
            $rules['default_open_ended_submission_type'] = Rule::in(['file', 0]);
        }

        $rules['libretexts_url'] = 'nullable|url';


        if ($this->assessment_type === 'delayed') {
            $rules['file_upload_mode'] = Rule::in(['compiled_pdf', 'individual_assessment', 'both']);
        }
        if ($this->assessment_type === 'real time' && $this->scoring_type === 'p') {
            $rules['number_of_allowed_attempts'] = ['required', Rule::in(['1', '2', '3', '4', 'unlimited'])];
            if ($this->number_of_allowed_attempts !== '1') {
               $rules['number_of_allowed_attempts_penalty'] = ['required', new IsValidNumberOfAllowedAttemptsPenalty($this->number_of_allowed_attempts)];
            }
            $rules['solutions_availability'] = ['required', Rule::in(['automatic', 'manual'])];
        }

        $new_assign_tos = [];
        foreach ($this->assign_tos as $key => $assign_to) {
            $new_assign_tos[$key]['available_from'] = "{$assign_to['available_from_date']} {$assign_to['available_from_time']}";
            $new_assign_tos[$key]['due'] = "{$assign_to['due_date']} {$assign_to['due_time']}";
            if ($this->late_policy !== 'not accepted') {
                $rules['final_submission_deadline_' . $key] = new IsADateLaterThan($this->{'due_' . $key}, 'due', 'late policy deadline');
            }
            $rules['due_' . $key] = new IsADateLaterThan($this->{'available_from_' . $key}, 'available on', 'due');
            $rules['available_from_date_' . $key] = 'required|date';
            $rules['available_from_time_' . $key] = 'required|date_format:H:i:00';
            $rules['due_time_' . $key] = 'required|date_format:H:i:00';
            $rules['groups_' . $key] = 'required';
        }
        switch ($this->source) {
            case('a'):
                $rules['points_per_question'] = ['required', Rule::in('number of points', 'question weight')];
                if ($this->points_per_question === 'number of points') {
                    $rules['default_points_per_question'] = 'numeric|min:0|max:1000';
                }
                if ($this->points_per_question === 'question weight') {
                    $rules['total_points'] = ['numeric', 'min:0', 'not_in:0', 'max:1000'];
                    if ($this->route()->getActionMethod() === 'update') {
                        $assignment_id = $this->route()->parameters()['assignment']->id;
                        if (abs(Assignment::find($assignment_id)->total_points - $this->total_points) >=PHP_FLOAT_EPSILON) {
                            $rules['total_points'][] = new IsNotOpenOrNoSubmissions($new_assign_tos);
                        }
                    }
                }
                if ((int)($this->randomizations) === 1) {
                    $rules['number_of_randomized_assessments'] = ['required',
                        'integer',
                        'gt:0',
                        new IsNotClickerAssessment($this->assessment_type),
                    ];
                    if ($this->route()->getActionMethod() === 'update') {
                        $assignment_id = $this->route()->parameters()['assignment']->id;
                        if (Assignment::find($assignment_id)->number_of_randomized_assessments !== $this->number_of_randomized_assessments) {
                            $rules['number_of_randomized_assessments'][] = new HasNoRandomizedAssignmentQuestions($assignment_id);
                        }
                    }

                }
                break;
            case('x'):
                $rules['external_source_points'] = 'required|integer|min:0|max:200';
                break;

        }
        if ($this->assessment_type === 'learning tree') {
            $rules['min_time_needed_in_learning_tree'] = 'required|integer|min:0|max:20';
            $rules['percent_earned_for_exploring_learning_tree'] = 'required|integer|min:0|max:100';
            $rules['submission_count_percent_decrease'] = new IsValidSubmissionCountPercentDecrease($this->percent_earned_for_exploring_learning_tree);

        }

        if ($this->assessment_type === 'clicker') {
            $rules['default_clicker_time_to_submit'] = new IsValidPeriodOfTime();
        }
        if ($this->late_policy === 'deduction') {
            //has to be at least one or division by 0 issue in setScoreBasedOnLatePolicy
            //deducting 100% makes no sense!
            $rules['late_deduction_percent'] = 'required|integer|min:1|max:99';
            if (!$this->late_deduction_applied_once) {
                $rules['late_deduction_application_period'] = new IsValidPeriodOfTime();
            }
        }

        if ($this->scoring_type === 'c') {
            $rules['scoring_type'] = [new isValidLatePolicyForCompletedScoringType($this->late_policy),
                new isValidAssesmentTypeForScoringType($this->assessment_type)];
            $rules['default_completion_scoring_mode'] = ['required', new isValidDefaultCompletionScoringType($this->completion_split_auto_graded_percentage)];

        }

        return $rules;
    }

    public function messages()
    {
        $messages = [];

        foreach ($this->assign_tos as $key => $assign_to) {
            $messages["groups_{$key}.required"] = 'The assign to field is required.';
            $messages["available_from_date_{$key}.required"] = 'This date is required.';
            $messages["available_from_time_{$key}.required"] = 'This time is required: H:i:00';
            $messages["due_time_{$key}.required"] = 'This time is required: H:i:00';
        }
        $messages['libretexts_url.url'] = "The URL should be of the form https://some-library.libretexts.org/some-page.";
        return $messages;
    }
}
