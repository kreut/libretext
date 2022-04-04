<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class IsValidNumberOfResets implements Rule
{
    private $number_of_successful_branches_for_a_reset;
    private $branch_items;
    /**
     * @var string
     */
    private $message;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($number_of_successful_branches_for_a_reset, $branch_items)
    {
        $this->number_of_successful_branches_for_a_reset = $number_of_successful_branches_for_a_reset;
        $this->branch_items = $branch_items;
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
        $success = true;
        if ($value !== 'maximum possible') {
            $num_branch_items = count($this->branch_items);
            $plural = $this->number_of_successful_branches_for_a_reset > 1 ? 'es' : '';
            if ($this->number_of_successful_branches_for_a_reset * $value > $num_branch_items) {
                $this->message = "Students must complete $this->number_of_successful_branches_for_a_reset branch$plural and are allowed $value resets.  But there are only $num_branch_items total branches in the Learning Tree.";
                $success = false;
            }
        }
        return $success;
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
