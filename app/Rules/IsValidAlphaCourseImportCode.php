<?php

namespace App\Rules;

use App\AlphaCourseImportCode;
use Illuminate\Contracts\Validation\Rule;

class IsValidAlphaCourseImportCode implements Rule
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
        return AlphaCourseImportCode::where('import_code', '=', $value)->exists();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'That is not a valid import code.';
    }
}
