<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Carbon\Carbon;
use \Exception;

class IsADateLaterThan implements Rule
{

    private $earlier_date;
    private $earlier_date_name;
    private $later_date_name;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($earlier_date, $earlier_date_name, $later_date_name)
    {
        $this->earlier_date = $earlier_date;
        $this->earlier_date_name = $earlier_date_name;
        $this->later_date_name = $later_date_name;
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
        try {
            return Carbon::parse($value) > Carbon::parse($this->earlier_date);
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
        return "The {$this->later_date_name} should be a valid date/time and should come after the {$this->earlier_date_name} date/time.";
    }
}
