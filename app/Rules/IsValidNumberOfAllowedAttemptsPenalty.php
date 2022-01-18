<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class IsValidNumberOfAllowedAttemptsPenalty implements Rule
{
    /**
     * @var string
     */
    private $message;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($number_of_allowed_attempts)
    {
        $this->number_of_allowed_attempts = $number_of_allowed_attempts;
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
        if (!filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 100]])) {
            $this->message = "$value is not between 0 and 100";
            return false;
        }

        if (in_array($this->number_of_allowed_attempts, [2, 3, 4])) {
            $max_deduction = $value * ((int)$this->number_of_allowed_attempts - 1);
            if ($max_deduction > 100) {
                $this->message = "Based on the number of attempts you are giving your students and the percent deduction, your total potential deduction of $max_deduction% is more than 100%.";
                return false;
            }
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
