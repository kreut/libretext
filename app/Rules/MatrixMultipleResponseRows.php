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
     * @param string $attribute
     * @param mixed $value
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
            if (!$row['header']) {
                $passes = false;
                $message[$key]['header'] = 'Row header is required.';
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
    public
    function message(): string
    {
        return $this->message;
    }
}
