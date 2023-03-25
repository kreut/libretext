<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class IsCorrectNumberOfSelectChoices implements Rule
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
        $passes = true;
        switch ($this->qti_array['dropDownRationaleType']) {
            case('dyad'):
                if (count($this->qti_array['inline_choice_interactions']) !== 2) {
                    $this->message = "Drop-down rationale dyads should have exactly 2 drop downs.";
                    $passes = false;
                }
                break;
            default:
                $this->message = "{$this->qti_array['dropDownRationaleType']} is not a valid drop-down rationale type.";
                $passes = false;
        }
        return $passes;
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
