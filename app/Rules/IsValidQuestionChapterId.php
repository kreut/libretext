<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class IsValidQuestionChapterId implements Rule
{
    private $question_subject_id;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($question_subject_id)
    {
        $this->question_subject_id = $question_subject_id;
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
        return $value === null || DB::table('question_chapters')->where('id', $value)
                ->where('question_subject_id', $this->question_subject_id)
                ->exists();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The question chapter is not valid.';
    }
}
