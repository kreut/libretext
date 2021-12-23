<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class IsValidStudentNameConfirmation implements Rule
{
    private $student;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($student)
    {
        $this->student=$student;
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

       return  $value === $this->student->first_name . ' '  . $this->student->last_name;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return "For confirmation, please enter {$this->student->first_name} {$this->student->last_name}.";
    }
}
