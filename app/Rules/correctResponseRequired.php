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
    public function __construct($qti_json)
    {

        $this->qti_simple_choices = json_decode($qti_json,true)['simpleChoice'];
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
        foreach ($this->qti_simple_choices as $simple_choice) {
            if ($simple_choice['correctResponse']) {
                return true;
            }
        }
        return false;
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
