<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class atLeastTwoMatches implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($qti_array)
    {
        $this->qti_array = $qti_array;
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
        return count($this->qti_array['possibleMatches']) >=2;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'There should be at least 2 matching choices.';
    }
}
