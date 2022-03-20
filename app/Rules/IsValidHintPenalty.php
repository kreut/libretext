<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class IsValidHintPenalty implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->message = '';
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
        $value = str_replace('%', '', $value);
        if (filter_var($value, FILTER_VALIDATE_INT) !== 0 && !filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 100]])) {
            $this->message = "The hint penalty should be an integer between 0 and 100";
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
