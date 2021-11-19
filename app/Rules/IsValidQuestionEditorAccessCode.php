<?php

namespace App\Rules;

use App\QuestionEditorAccessCode;
use Illuminate\Contracts\Validation\Rule;

class IsValidQuestionEditorAccessCode implements Rule
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
        return QuestionEditorAccessCode::where('access_code', '=', $value)->exists();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'That is not a valid access code.';
    }
}
