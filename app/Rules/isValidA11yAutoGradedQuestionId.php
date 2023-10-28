<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class isValidA11yAutoGradedQuestionId implements Rule
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
    public function passes($attribute, $value): bool
    {
        if (in_array($value, [null, ''])) {
            return true;
        }
        $adapt_id = $value;
        if (strpos($value, '-') !== false) {
            $pos = strpos($value, '-');
            $adapt_id = substr($value, $pos + 1);
        }
        $auto_graded_questions_exists = DB::table('questions')
            ->where('id', $adapt_id)
            ->where('technology', '<>', $adapt_id)
            ->first();
        if (!$auto_graded_questions_exists) {
            $this->message = "There's no auto-graded question with ADAPT ID $value.";
            return false;
        }
        return true;
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
