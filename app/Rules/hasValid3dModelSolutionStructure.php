<?php

namespace App\Rules;

use Exception;
use Illuminate\Contracts\Validation\Rule;

class hasValid3dModelSolutionStructure implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
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
        try {
            $selectedIndex = json_decode($value)->selectedIndex;
            if (!is_int($selectedIndex) || $selectedIndex < 0) {
                $passes = false;
            }
        } catch (Exception $e) {
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
        return 'Please first select a piece from the model.';
    }
}
