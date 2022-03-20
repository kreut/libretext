<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class IsValidNumberOfSuccessfulAssessments implements Rule
{
    private $learning_tree_success_level;
    private $branch_items;
    /**
     * @var string
     */
    private $message;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($learning_tree_success_level, $branch_items)
    {
        $this->learning_tree_success_level = $learning_tree_success_level;
        $this->branch_items = $branch_items;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        switch ($this->learning_tree_success_level) {
            case('tree'):
                $num_assessments = 0;
                foreach ($this->branch_items as $branch_item) {
                    $num_assessments += $branch_item['assessments'];
                }
                if ($value > $num_assessments) {
                    $complete_s = $value > 1 ? 's' : '';
                    $only_s = $num_assessments > 1 ? 's' : '';
                    $this->message = "You want students to complete $value assessment$complete_s but there are only $num_assessments assessment$only_s in the Learning Tree.";
                    return false;
                }
                break;
            case('branch'):
                foreach ($this->branch_items as $branch_item) {

                    if ($value > $branch_item['assessments']) {
                        $has_s = $branch_item['assessments'] > 1 ? 's' : '';
                        $complete_s = $value > 1 ? 's' : '';
                        $this->message = "{$branch_item['description']} only has {$branch_item['assessments']} assessment$has_s and you require that your students complete $value assessment$complete_s within that branch.";
                        return false;
                    }
                }
                break;
        }
        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return $this->message;
    }
}
