<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class IsValidSketcherStructure implements Rule
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
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $solution_structure = json_decode($value,1);
        $passes = false;
        foreach (['atoms', 'bonds', 'arrows'] as $item) {
            if (isset($solution_structure[$item]) && $solution_structure[$item]) {
                $passes= true;
            }
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
        return 'You have not entered a structure into the Sketcher.';
    }
}
