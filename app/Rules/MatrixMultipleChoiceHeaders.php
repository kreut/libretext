<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class MatrixMultipleChoiceHeaders implements Rule
{
    private $headers;
    /**
     * @var array
     */
    private $message;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($headers)
    {
        $this->headers = $headers;
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
        $message = [];
        if (count($this->headers) < 3) {
            $passes = false;
            $message['general'] = 'There should be at least 3 columns.';
        }
        foreach ($this->headers as $key => $header) {
            if (!$header) {
                $passes = false;
                $message['specific'][$key] = "Header text is required.";
            }
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
    public function message()
    {
        return $this->message;
    }
}
