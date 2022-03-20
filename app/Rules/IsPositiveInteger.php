<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class IsPositiveInteger implements Rule
{
    private $formatted_attribute;
    /**
     * @var string
     */
    private $message;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($formatted_attribute)
    {
        $this->formatted_attribute = $formatted_attribute;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value)
    {

        if ($value === null) {
            $this->message = "$this->formatted_attribute is required.";
            return false;
        }
        if (!filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
            $this->message = "$this->formatted_attribute should be a positive integer.";
            return false;
        }
        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return $this->message;
    }
}
