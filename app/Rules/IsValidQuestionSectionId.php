<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class IsValidQuestionSectionId implements Rule
{
    private $question_chapter_id;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($question_chapter_id)
    {
       $this->question_chapter_id = $question_chapter_id;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        return $value === null || DB::table('question_sections')->where('id', $value)
                ->where('question_chapter_id',$this->question_chapter_id)
                ->exists();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The question section is not valid.';
    }
}
