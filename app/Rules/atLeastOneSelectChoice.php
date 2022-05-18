<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use stdClass;

class atLeastOneSelectChoice implements Rule
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

        return $this->qti_array['inline_choice_interactions'];
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The question needs at least one select choice.';
    }
}
