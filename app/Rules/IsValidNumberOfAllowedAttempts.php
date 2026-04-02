<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class IsValidNumberOfAllowedAttempts implements Rule
{
    public function passes($attribute, $value): bool
    {
        if ($value === 'unlimited') return true;
        return ctype_digit((string) $value) && (int) $value >= 1;
    }

    public function message(): string
    {
        return 'The number of allowed attempts must be a positive whole number or "unlimited".';
    }
}
