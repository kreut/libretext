<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class BowTieItems implements Rule
{
    /**
     * @var string
     */
    private $message;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {

        $this->message = '';
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {

        $passes = true;
        $num_distractors = 0;
        $message = [];
        foreach ($value as $item) {
            if (!$item['value']) {
                $passes = false;
                $num_distractors += +!$item['correctResponse'];
                $message['specific'][$item['identifier']] = "Text is required.";
            }
        }
        if (!$num_distractors) {
            $message['general'] = "There should be at least one distractor.";
        }
        if ($message) {
            $this->message = json_encode($message);
        }
        return $passes;


    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return $this->message;
    }
}
