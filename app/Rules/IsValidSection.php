<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class IsValidSection implements Rule
{
    private $course;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($course)
    {
       $this->course = $course;
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

        return in_array($value, $this->course->sections->pluck('id')->toArray());
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Please choose a section.';
    }
}
