<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class MatrixMultipleResponseColumns implements Rule
{
    private $headers;
    private $rows;
    /**
     * @var string
     */
    private $message;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($headers, $rows)
    {
        $this->headers = $headers;
        $this->rows = $rows;
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
        $message = [];

        if (count($this->headers) < 2) {
            $passes = false;
            $message['general'] = "There should be at least 2 columns.";
        }

        foreach ($this->headers as $key => $header) {
            if (!$header) {
                $passes = false;
                $message['specific'][$key] = "Header text is required.";
            }
        }
//all column headers but the first
        for ($key = 0; $key < count($this->headers) - 1; $key++) {
            $at_least_one_checked = false;
            foreach ($this->rows as $row) {
                if ($row['responses'][$key]['correctResponse']) {
                    $at_least_one_checked = true;
                }
            }
            if (!$at_least_one_checked) {
                $passes = false;
                $at_least_one_checked_message = "You should have at least 1 item checked in this column.";
                if (isset($message['specific'][$key+1])) {
                    $message['specific'][$key+1]  .= "  $at_least_one_checked_message";
                } else {
                    $message['specific'][$key+1]  = $at_least_one_checked_message;
                }
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
