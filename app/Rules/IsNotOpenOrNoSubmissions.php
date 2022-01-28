<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class IsNotOpenOrNoSubmissions implements Rule
{
    private $assign_tos;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($assign_tos)
    {
        $this->assign_tos = $assign_tos;
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

        foreach ($this->assign_tos as $assign_to) {
            if (strtotime($assign_to['available_from']) < time() && strtotime($assign_to['due']) > time()) {
              return false;
            }
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
        return "The total points can't be changed while the assignment is open.";
    }
}
