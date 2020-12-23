<?php

namespace App\Http\Requests;


use App\Rules\IsValidPeriodOfTime;
use App\Rules\IsADateLaterThan;
use App\Rules\IsValidSubmissionCountPercentDecrease;
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

        $this->merge(['due' => $this->due_date . ' ' . $this->due_time]);
        $this->merge(['available_from' => $this->available_from_date . ' ' . $this->available_from_time]);
        $this->merge(['late_policy_deadline' => $this->late_policy_deadline_date . ' ' . $this->late_policy_deadline_time]);

        $rules = [
            'name' => ['required', 'max:255'],
            'available_from_date' => 'required|date',
            'available_from_time' => 'required|date_format:H:i:00',
            'due' => new IsADateLaterThan($this->available_from, 'available on', 'due'),
            'source' => Rule::in(['a', 'x']),
            'scoring_type' => Rule::in(['c', 'p']),
            'late_policy' => Rule::in(['not accepted', 'marked late', 'deduction']),
            'assignment_group_id' => 'required|exists:assignment_groups,id',
            'include_in_weighted_average' => Rule::in([0, 1]),
            'submission_files' => Rule::in(['q', 'a', 0]),
        ];
        if ($this->scoring_type === 'p') {
            switch ($this->source) {
                case('a'):
                    $rules['default_points_per_question'] = 'required|integer|min:0|max:100';
                    break;
                case('x'):
                    $rules['external_source_points'] = 'required|integer|min:0|max:200';
                    break;
            }
        }
        if ($this->assessment_type === 'learning tree') {
            $rules['min_time_needed_in_learning_tree'] = 'required|integer|min:0|max:20';
            $rules['percent_earned_for_exploring_learning_tree'] = 'required|integer|min:0|max:100';
            $rules['submission_count_percent_decrease'] = new IsValidSubmissionCountPercentDecrease($this->percent_earned_for_exploring_learning_tree);

        }

        if ($this->late_policy === 'deduction') {
            //has to be at least one or division by 0 issue in setScoreBasedOnLatePolicy
            //deducting 100% makes no sense!
            $rules['late_deduction_percent'] = 'required|integer|min:1|max:99';
        }
        if (!$this->late_deduction_applied_once) {
            $rules['late_deduction_application_period'] = new IsValidPeriodOfTime();
        }

        if ($this->late_policy !== 'not accepted') {
            $rules['late_policy_deadline'] = new IsADateLaterThan($this->due, 'due', 'late policy deadline');
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'available_from_time.required' => 'A time is required.',
            'due_time.required' => 'A time is required.'
        ];
    }
}
