<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class IsNotClickerAssessment implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($assessment_type)
    {
        $this->assessment_type = $assessment_type;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        return $this->assessment_type !== 'clicker';
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return "You can't use a clicker with randomized assessments.";
    }
}
