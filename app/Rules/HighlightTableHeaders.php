<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class HighlightTableHeaders implements Rule
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
    public function passes($attribute, $value)
    {
        $message = [];
        $passes = true;

        foreach ($value as $key => $header) {
            if (!$header) {
                $passes = false;
            }
            $message[$key] = !$header ?  'Text is required.': '';

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
    public
    function message()
    {
        return $this->message;
    }
}
