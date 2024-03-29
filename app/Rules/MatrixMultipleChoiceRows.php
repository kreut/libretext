<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class MatrixMultipleChoiceRows implements Rule
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
        if (count($this->rows) === 1) {
            $passes = false;
            $message['general'] = 'There should be at least 2 rows.';
        }
        foreach ($this->rows as $key => $row) {
            if (!$row['label']){
                $passes = false;
                $message['specific'][$key]['label'] = "Row header is required.";
            }
            if ($row['correctResponse'] === null){
                $passes = false;
                $message['specific'][$key]['correctResponse'] = "Correct response is required.";
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
