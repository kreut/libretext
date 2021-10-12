<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class isValidDefaultCompletionScoringType implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($completion_split_auto_graded_percentage)
    {

        $this->completion_split_auto_graded_percentage = $completion_split_auto_graded_percentage;
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

        $this->message = '';
        if (!in_array($value, ['100% for either', 'split'])) {
            $this->message = "That is not a valid default completion scoring mode.";
            return false;
        }
        if ($value === 'split'
            && (!is_numeric($this->completion_split_auto_graded_percentage)
                || ($this->completion_split_auto_graded_percentage < 0)
                || ($this->completion_split_auto_graded_percentage > 100))) {
            $this->message = 'The auto-graded percent should be a number between 0 and 100.';
            return false;
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
