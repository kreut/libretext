<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class IsValidNumberOfSuccessfulBranches implements Rule
{
    /**
     * @var string
     */
    private $message;
    private $branch_items;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($branch_items)
    {
        $this->branch_items = $branch_items;
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
        $num_learning_branches = count($this->branch_items);
        if (count($this->branch_items) < $value) {
            $this->message = "The Learning Tree only has $num_learning_branches branches but students need to successfully complete a minimum of $value branches before they can resubmit.";
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
