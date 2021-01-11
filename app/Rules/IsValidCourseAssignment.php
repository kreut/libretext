<?php

namespace App\Rules;

use App\Assignment;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class IsValidCourseAssignment implements Rule
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
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $assignment = new Assignment();
        return $assignment->idByCourseAssignmentUser($value);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'That is not an assignment from one of your courses.';
    }
}
