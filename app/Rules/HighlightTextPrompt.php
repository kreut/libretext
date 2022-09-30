<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class HighlightTextPrompt implements Rule
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
    public function passes($attribute, $value)
    {
        $passes = true;
        preg_match_all('/(\[.*?])/', $value, $matches);

        if (!$matches || count($matches[0]) < 2) {
            $passes = false;
            $this->message = "You need at least two bracketed terms.";
        }

        if ($matches && count($matches[0]) > 10) {
            $passes = false;
            $this->message = "You need at most 10 bracketed terms.";
        }
        $current_matches = [];
        foreach ($matches[0] as $match) {
            if ($match === '[]') {
                $passes = false;
                $this->message = "None of your brackets should be empty.";
            } else {
                if (in_array($match, $current_matches)) {
                    $passes = false;
                    $this->message = "$match is repeated multiple times.  The highlighted text should be unique.";
                }
                $current_matches[] = $match;
            }
        }
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
