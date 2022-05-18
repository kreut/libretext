<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class atLeastOneFillInTheBlank implements Rule
{
    private $qti_array;

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
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        preg_match('/<u>(.*?)<\/u>/', $this->qti_array['itemBody']['textEntryInteraction'], $matches);
        return count($matches) > 0;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'There should be at least one fill in the blank.';
    }
}
