<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class DropDownTableRows implements Rule
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
        foreach ($this->rows as $key => $row) {
            if (!$row['header']) {
                $passes = false;
                $message['specific'][$key]['header'] = "Row header is required.";
            }
            if (count($row['responses']) === 1) {
                $passes = false;
                $message['general'] = 'You need at least 1 Distractor.';
            }
            foreach ($row['responses'] as $item) {
                if ($item['value'] === null) {
                    $message['specific'][$key][$item['identifier']] = 'Text is required.';
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
    public function message(): string
    {
        return $this->message;
    }
}
