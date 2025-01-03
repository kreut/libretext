<?php

namespace App\Http\Requests;


use App\Assignment;
use App\AutoRelease;
use App\Course;
use App\Rules\HasNoRandomizedAssignmentQuestions;
use App\Rules\IsNotClickerAssessment;
use App\Rules\IsNotOpenOrNoSubmissions;
use App\Rules\IsPositiveInteger;
use App\Rules\isValidDefaultCompletionScoringType;
use App\Rules\IsValidHintPenalty;
use App\Rules\IsValidNumberOfAllowedAttemptsPenalty;
use App\Rules\IsValidPeriodOfTime;
use App\Rules\IsADateLaterThan;
use App\Rules\IsValidAssesmentTypeForScoringType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class StoreAssignmentProperties extends FormRequest
{


    /**
     * @var mixed
     */
    private $instructions;

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
     * @param Course $course
     * @return array
     */
    public function rules(Course $course): array
    {

        $formative = ($this->course_id && $course->find($this->course_id)->formative) || $this->formative;
        if ($this->user()->role === 5) {
            $unique = Rule::unique('assignments')
                ->where('course_id', $this->course_id);
            if ($this->route()->getActionMethod() === 'update') {
                $unique->ignore($this->route()->parameters()['assignment']->id);
            }
            $rules['name'] = ['required', 'max:255', $unique];
        } else {
            $rules = [
                'source' => Rule::in(['a', 'x']),
                'scoring_type' => Rule::in(['c', 'p']),
                'late_policy' => Rule::in(['not accepted', 'marked late', 'deduction']),
                'include_in_weighted_average' => Rule::in([0, 1]),
                'instructions' => 'max:10000',
                'default_open_ended_submission_type' => Rule::in(['file', 'rich text', 'audio', 0]),
                'notifications' => Rule::in([0, 1]),
                'formative' => Rule::in([0, 1])
            ];
            if (!$formative) {
                $rules['assignment_group_id'] = 'required|exists:assignment_groups,id';
                $rules['can_submit_work'] = ['required', Rule::in([0, 1])];
                $auto_releases = ['auto_release_shown',
                    'auto_release_show_scores',
                    'auto_release_solutions_released',
                    'auto_release_students_can_view_assignment_statistics'];
                foreach ($auto_releases as $auto_release) {
                    if ($this->{$auto_release}) {
                        $rules[$auto_release] = new IsValidPeriodOfTime();
                        if ($auto_release !== 'auto_release_shown' && $this->late_policy !== 'not accepted') {
                            $rules[$auto_release . "_after"] = 'required';
                        }
                    }
                    if ($this->late_policy !== 'not accepted'
                        && $auto_release !== 'auto_release_shown'
                        && $this->{$auto_release . "_after"}) {
                        $rules[$auto_release] = 'required';
                    }
                }
            }
            if ($this->is_template) {

                $unique = Rule::unique('assignment_templates')
                    ->where('user_id', $this->user()->id);
                if ($this->route()->getActionMethod() === 'update') {
                    $unique->ignore($this->route()->parameters()['assignmentTemplate']->id);
                }

                $rules['template_name'] = ['required', 'max:255', $unique];
                $rules['template_description'] = ['required', 'min:5', 'max:255'];
                $rules['assign_to_everyone'] = ['required', Rule::in([0, 1])];

            } else {
                if (!$this->formative && $course->find($this->course_id)->lms) {
                    $rules['lms_grade_passback'] = ['required', Rule::in(['automatic', 'manual'])];
                }
                $unique = Rule::unique('assignments')
                    ->where('course_id', $this->course_id);
                if ($this->route()->getActionMethod() === 'update') {
                    $unique->ignore($this->route()->parameters()['assignment']->id);
                }
                $rules['name'] = ['required', 'max:255', $unique];

            }


            if ($this->file_upload_mode === 'compiled_pdf') {
                $rules['default_open_ended_submission_type'] = Rule::in(['file', 0]);
            }

            $rules['textbook_url'] = 'nullable|url';


            if ($this->assessment_type === 'delayed') {
                $rules['file_upload_mode'] = Rule::in(['compiled_pdf', 'individual_assessment', 'both']);
            }

            $rules['can_view_hint'] = ['required', Rule::in([0, 1])];
            if ((int)$this->can_view_hint === 1 && !$formative) {
                $rules['hint_penalty'] = [new IsValidHintPenalty()];
            }

            if ($formative) {
                $this->number_of_allowed_attempts_penalty = 0;
            }
            if (!$formative && in_array($this->assessment_type, ['real time', 'learning tree']) && $this->scoring_type === 'p') {
                $rules['number_of_allowed_attempts'] = ['required', Rule::in(['1', '2', '3', '4', 'unlimited'])];
                if ($this->number_of_allowed_attempts !== '1') {
                    $rules['number_of_allowed_attempts_penalty'] = ['required', new IsValidNumberOfAllowedAttemptsPenalty($this->number_of_allowed_attempts)];
                }
                if ($this->assessment_type === 'real time') {
                    $rules['solutions_availability'] = ['required', Rule::in(['automatic', 'manual'])];
                }
            }
            $new_assign_tos = [];
            if (!$this->is_template && !$formative) {
                foreach ($this->assign_tos as $key => $assign_to) {
                    $new_assign_tos[$key]['available_from'] = "{$assign_to['available_from_date']} {$assign_to['available_from_time']}";
                    $new_assign_tos[$key]['due'] = "{$assign_to['due_date']} {$assign_to['due_time']}";
                    if ($this->late_policy !== 'not accepted') {
                        $rules['final_submission_deadline_' . $key] = new IsADateLaterThan($this->{'due_' . $key}, 'due', 'late policy deadline');
                        $rules['final_submission_deadline_time_' . $key] = 'date_format:g:i A';
                    }
                    $rules['due_' . $key] = new IsADateLaterThan($this->{'available_from_' . $key}, 'available on', 'due');
                    $rules['available_from_date_' . $key] = 'required|date';
                    $rules['available_from_time_' . $key] = 'required|date_format:g:i A';
                    $rules['due_time_' . $key] = 'required|date_format:g:i A';
                    $rules['groups_' . $key] = 'required';
                }
            }
            switch ($this->source) {
                case('a'):
                    $rules['algorithmic'] = ['required', Rule::in(0, 1)];
                    $rules['points_per_question'] = ['required', Rule::in('number of points', 'question weight')];
                    if ($this->points_per_question === 'number of points') {
                        $rules['default_points_per_question'] = 'numeric|min:0|max:1000';
                    }
                    if ($this->points_per_question === 'question weight') {
                        $rules['total_points'] = ['numeric', 'min:0', 'not_in:0', 'max:1000'];
                        if (!$this->is_template && $this->route()->getActionMethod() === 'update') {
                            $assignment_id = $this->route()->parameters()['assignment']->id;
                            if (abs(Assignment::find($assignment_id)->total_points - $this->total_points) >= PHP_FLOAT_EPSILON) {
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
                        if (!$this->is_template && $this->route()->getActionMethod() === 'update') {
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

                $rules['min_number_of_minutes_in_exposition_node'] = ['required', 'numeric', 'gte:0'];
                $rules['reset_node_after_incorrect_attempt'] = ['required', Rule::in(0, 1)];
                $rules['number_of_successful_paths_for_a_reset'] = ['required', new IsPositiveInteger('Minimum number of successful paths')];

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
                $rules['scoring_type'] = ['required', new isValidAssesmentTypeForScoringType($this->assessment_type)];
                $rules['default_completion_scoring_mode'] = ['required', new isValidDefaultCompletionScoringType($this->completion_split_auto_graded_percentage)];

            }
        }
        return $rules;
    }

    /**
     * @return array
     */
    public function messages(): array
    {
        $autoRelease = new AutoRelease();
        $messages = $autoRelease->requestMessages();
        if (!$this->is_template) {
            foreach ($this->assign_tos as $key => $assign_to) {
                $index = $key + 1;
                $messages["groups_$key.required"] = 'The assign to field is required.';
                $messages["available_from_date_$key.required"] = 'This date is required.';
                $messages["available_from_time_$key.required"] = $this->getTimeFormatErrorMessage('available on', $index);
                $messages["available_from_time_$key.date_format"] = $this->getTimeFormatErrorMessage('available on', $index);
                $messages["due_time_$key.required"] = $this->getTimeFormatErrorMessage('due time', $index);
                $messages["due_time_$key.date_format"] = $this->getTimeFormatErrorMessage('due time', $index);
                $messages["final_submission_deadline_time_$key.date_format"] = $this->getTimeFormatErrorMessage('final submission deadline time', $index);
            }
            $messages['name.unique'] = "Assignment names must be unique with a course.";
        } else {
            $messages['template_name.unique'] = "Template names must be unique.";
        }
        $messages['textbook_url.url'] = "The URL should be of the form https://my-textbook-url.com/some-page.";
        return $messages;
    }

    /**
     * @param string $field
     * @param int $index
     * @return string
     */
    public function getTimeFormatErrorMessage(string $field, int $index): string
    {
        return "Time for $field $index needs a valid time such as 9:00 AM.";
    }
}
