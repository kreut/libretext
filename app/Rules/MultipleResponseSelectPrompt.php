<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class MultipleResponseSelectPrompt implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {

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
        $regex = '/\[[1-9]\d*]/';
        preg_match_all($regex, $value, $matches);
        return count($matches) === 1;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'There should be one set of brackets with a number in-between.';
    }
}
