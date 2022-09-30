<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class HighlightTextResponses implements Rule
{
    /**
     * @var false|string
     */
    private $message;

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
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        $passes = true;
        foreach ($value as $response) {
            if ($response['correctResponse'] === null) {
                $passes = false;
                $this->message[$response['identifier']] = "Please choose Correct Response or Distractor.";
            }
        }
        $this->message = json_encode($this->message);
        return $passes;
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
