<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class oneCauseAndTwoEffectsInBody implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($item_body)
    {
        $this->item_body = $item_body;
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

        return substr_count($this->item_body, "[condition]") === 1 && substr_count($this->item_body, "[rationale]") === 2;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public
    function message()
    {
        return 'The prompt should contain one [condition] and two separate instances of [rationale].';
    }
}
