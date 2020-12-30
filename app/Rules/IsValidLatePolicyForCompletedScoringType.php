<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class IsValidLatePolicyForCompletedScoringType implements Rule
{
    private $late_policy;
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($late_policy)
    {
        $this->late_policy = $late_policy;
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
        return $this->late_policy === 'not accepted';
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Complete/Incomplete assignments must have a late policy of not accepted.';
    }
}
