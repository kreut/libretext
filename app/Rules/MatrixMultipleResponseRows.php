<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class MatrixMultipleResponseRows implements Rule
{
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
    public function __construct($rows)
    {
        $this->rows = $rows;
        $this->message = '';
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        $passes = true;
        $message = [];

        if (!count($this->rows)) {
            $passes = false;
            $message['general'] = 'There should be at least 1 row.';
        }

        foreach ($this->rows as $key => $row) {
            $correct_response_exists = false;
            foreach ($row as $row_key => $value) {
                if ($row_key === 0 ) {
                    if (!$value) {
                        $passes = false;
                        $message[$key]['header'] = 'Row header is required.';
                    }
                }
               if ($row_key !== 0 ) {
                   if ($value) {
                       $correct_response_exists = true;
                   }
               }
            }
            if (!$correct_response_exists) {
                $passes = false;
                $message[$key]['at_least_one_marked_correct'] = 'At least one should be marked correct.';
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
    public function message(): string
    {
        return $this->message;
    }
}
