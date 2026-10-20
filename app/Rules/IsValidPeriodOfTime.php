<?php

namespace App\Rules;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\Rule;
use \Exception;

class IsValidPeriodOfTime implements Rule
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
        if (!is_string($value) || trim($value) === '') {
            return false;
        }
        try {
            $base = CarbonImmutable::now();
            //must be an actual length of time: "0 minutes" or a negative interval doesn't count
            return $base->add($value)->greaterThan($base);
        } catch (Exception $e) {
            return false;
        }

    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'This should be a valid period of time such as 1 hour, 2 days, or 5 minutes.';
    }
}
