<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class atLeastTwoResponses implements Rule
{
    private $qti_simple_choices;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($qti_simple_choices)
    {
        $this->qti_simple_choices = $qti_simple_choices;
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
        return count($this->qti_simple_choices) >=2;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'You should have at least 2 responses.';
    }
}
