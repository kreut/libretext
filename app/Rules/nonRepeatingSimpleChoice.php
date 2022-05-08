<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class nonRepeatingSimpleChoice implements Rule
{
    /**
     * @var mixed
     */
    private $repeated_simple_choice;
    private $qti_simple_choices;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($qti_simple_choices)
    {
        $this->qti_simple_choices = $qti_simple_choices;
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
        $counts = [];
        foreach ($this->qti_simple_choices as $choice) {
            $counts[$choice] = !isset($counts[$choice]) ? 1 : $counts[$choice] + 1;
            if ($counts[$choice]>1){
                $this->repeated_simple_choice = $choice;
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
        return "The response '$this->repeated_simple_choice' appears more than once.";
    }
}
