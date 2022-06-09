<?php

namespace App\Rules;

use App\Question;
use Illuminate\Contracts\Validation\Rule;

class IsValidMatchingPrompt implements Rule
{

    /**
     * @var string
     */
    private $message;
    /**
     * @var mixed|null
     */
    private $question_id;
    private $qti_json;
    private $question_type;

    public function __construct($question_type, $qti_json, $question)
    {
        $this->message = '';
        $this->question_id = $question ? $question->id : null;
        $this->qti_json = $qti_json;
        $this->question_type = $question_type;
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
        $like_question_id = $question->qtiMatchingQuestionExists($this->question_type, $this->qti_json, $value, $this->question_id);
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
