<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class IsValidCourseNameConfirmation implements Rule
{
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
       return $this->course->name === $value;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return "Please enter {$this->course->name} to confirm that you would like to reset the course.";
    }
}
