<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class TableHeaders implements Rule
{
    private $headers;
    /**
     * @var array
     */
    private $message;
    /**
     * @var mixed|null
     */
    private $min_columns;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($headers, $min_columns=null)
    {
        $this->headers = $headers;
        $this->message = '';
        $this->min_columns= $min_columns;
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
        if ($this->min_columns) {
            if (count($this->headers) < $this->min_columns) {
                $passes = false;
                $message['general'] = "There should be at least {$this->min_columns} columns.";
            }
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
