<?php

namespace App\Rules;

use App\School;
use Illuminate\Contracts\Validation\Rule;

class IsValidSchoolName implements Rule
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
        return $value
            ? School::where('name', $value)->first()
            : true;

    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'This it not one of the schools in our database.  If you would like your school added, please contact us.';
    }
}
