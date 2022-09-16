<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class DragAndDropClozePrompt implements Rule
{
    private $responses;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($responses)
    {
        $this->responses = $responses;
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

        return $this->responses;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'You need at least one Correct Response in your prompt.';
    }
}
