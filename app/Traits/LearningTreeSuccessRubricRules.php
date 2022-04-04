<?php


namespace App\Traits;

use App\Rules\IsPositiveInteger;
use App\Rules\IsValidNumberOfResets;
use App\Rules\IsValidNumberOfSuccessfulAssessments;
use App\Rules\IsValidNumberOfSuccessfulBranches;
use Illuminate\Validation\Rule;

trait LearningTreeSuccessRubricRules
{
    /**
     * @param $request
     * @return array
     */
    public function learningTreeSuccessRubricRules($request): array
    {

        $rules['learning_tree_success_level'] = ['required', Rule::in(['branch', 'tree'])];
        if ($request->learning_tree_success_level === 'branch') {
            $rules['number_of_successful_branches_for_a_reset'] = ['required', new IsPositiveInteger('Minimum number of successful branches')];
            $rules['number_of_resets'] = ['required', Rule::in([1, 2, 3, 4, 5])];
            if ($request->branch_items) {
                $rules['number_of_successful_branches_for_a_reset'][] = new IsValidNumberOfSuccessfulBranches($request->branch_items);
                $rules['number_of_resets'][] = new IsValidNumberOfResets($request->number_of_successful_branches_for_a_reset, $request->branch_items);
            }

        }
        $rules['learning_tree_success_criteria'] = ['required', Rule::in(['time based', 'assessment based'])];
        switch ($request->learning_tree_success_criteria) {
            case('time based'):
                $rules['min_time'] = ['required', new IsPositiveInteger('Minimum time')];
                break;
            case('assessment based'):
                $rules['min_number_of_successful_assessments'] = ['required', new IsPositiveInteger('Minimum number of successful assessments')];
                if ($request->branch_items) {
                    $rules['min_number_of_successful_assessments'][] = new IsValidNumberOfSuccessfulAssessments($request->learning_tree_success_level, $request->branch_items);
                }
                break;
        }
        $rules['free_pass_for_satisfying_learning_tree_criteria'] = ['required', Rule::in([0, 1])];
        return $rules;

    }


}
