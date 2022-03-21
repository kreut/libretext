<?php


namespace App\Traits;

use App\Rules\IsPositiveInteger;
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
            $rules['min_number_of_successful_branches'] = [new IsPositiveInteger('Minimum number of successful branches')];
            if ($request->branch_items){
                $rules['min_number_of_successful_branches'][] = new IsValidNumberOfSuccessfulBranches($request->branch_items);
            }
        }
        $rules['learning_tree_success_criteria'] = [Rule::in(['time based', 'assessment based'])];
        switch ($request->learning_tree_success_criteria) {
            case('time based'):
                $rules['min_time_spent'] = [new IsPositiveInteger('Minimum time spent')];
                break;
            case('assessment based'):
                $rules['min_number_of_successful_assessments'] = [new IsPositiveInteger('Minimum number of successful assessments')];
                if ($request->branch_items){
                    $rules['min_number_of_successful_assessments'][] = new IsValidNumberOfSuccessfulAssessments($request->learning_tree_success_level, $request->branch_items);
                }
                break;
        }
        $rules['reset_points'] = ['required', Rule::in([0, 1])];
        return $rules;

    }


}
