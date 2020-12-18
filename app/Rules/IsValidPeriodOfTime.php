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
        $is_valid = true;
        $date = CarbonImmutable::now();
        try {
            $date->add($value)->calendar();
        } catch (Exception $e){
           $is_valid = false;
        }
        return $is_valid;

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
