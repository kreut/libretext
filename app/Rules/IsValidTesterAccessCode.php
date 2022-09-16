<?php

namespace App\Rules;

use App\TesterAccessCode;
use Illuminate\Contracts\Validation\Rule;

class IsValidTesterAccessCode implements Rule
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
    public function passes($attribute, $value): bool
    {
        return TesterAccessCode::where('access_code', '=', $value)->exists();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return 'That is not a valid access code.';
    }
}
