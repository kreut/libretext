<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class SubmittedWorkFormatRule implements Rule
{
    /**
     * @var string
     */
    private $message;

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        foreach ($value as $submitted_work_format) {
            if (!in_array($submitted_work_format, ['file', 'video', 'audio'])) {
                $this->message = "$submitted_work_format is not a valid submitted work format.";
                return false;
            }
        }
        return true;
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
