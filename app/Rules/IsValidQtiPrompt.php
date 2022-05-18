<?php

namespace App\Rules;

use App\Question;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class IsValidQtiPrompt implements Rule
{
    private $qti_json;
    /**
     * @var string
     */
    private $message;
    /**
     * @var mixed|null
     */
    private $question_id;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($qti_json, $question)
    {
        $this->message = '';
        $this->question_id = $question ? $question->id : null;
        $this->qti_json = $qti_json;
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
        $question = new Question();
        $like_question_id = $question->qtiSimpleChoiceQuestionExists( $this->qti_json ,$value, $this->question_id);
        if ($like_question_id) {
            $this->message = "This question is identical to the native question with ADAPT ID $like_question_id.";
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
