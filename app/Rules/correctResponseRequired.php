<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class correctResponseRequired implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($qti_correct_response)
    {
        $this->qti_correct_response = $qti_correct_response;
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
        return is_string($this->qti_correct_response) && strlen($this->qti_correct_response);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return "You didn't select any of the responses as being correct.";
    }
}
