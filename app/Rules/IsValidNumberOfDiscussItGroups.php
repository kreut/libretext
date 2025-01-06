<?php

namespace App\Rules;

use App\Discussion;
use Illuminate\Contracts\Validation\Rule;

class IsValidNumberOfDiscussItGroups implements Rule
{
    /**
     * @var string
     */
    private $message;
    /**
     * @var int
     */
    private $question_id;
    /**
     * @var int
     */
    private $assignment_id;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(int $assignment_id, int $question_id)
    {
        $this->assignment_id = $assignment_id;
        $this->question_id = $question_id;
        $this->message = '';
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
        if (!isset($value) || !is_numeric($value) || $value < 1 || (int)$value != $value) {
            $this->message = "Should be an integer greater than or equal to 1.";
            return false;
        }
        if (Discussion::where('assignment_id', $this->assignment_id)
                ->where('question_id', $this->question_id)
                ->count() !== 0) {
            $this->message = 'If you would like to alter the number of groups, please first delete all discussions for this question.';
       return false;
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
