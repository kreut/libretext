<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class IsValidSubmissionCountPercentDecrease implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($percent_earned_for_exploring_learning_tree)
    {
        $this->percent_earned_for_exploring_learning_tree = $percent_earned_for_exploring_learning_tree;
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
        return filter_var(
                $value,
                FILTER_VALIDATE_INT,
                [
                    'options' => [
                        'min_range' => 0,
                        'max_range' =>  $this->percent_earned_for_exploring_learning_tree
                    ]
                ]
            );
    }


    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'This value should be an integer between 0 and your percent earned for exploring the Learning Tree.';
    }
}
