<?php

namespace App\Rules;

use App\Helpers\Helper;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class IsValidSubmissionScoreOverride implements Rule
{
    /**
     * @var int
     */
    private $assignment_id;
    /**
     * @var int
     */
    private $question_id;
    /**
     * @var string
     */
    private $message;

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
    public function passes($attribute, $value): bool
    {

        $assignment_question = DB::table('assignment_question')
            ->where('assignment_id', $this->assignment_id)
            ->where('question_id', $this->question_id)
            ->first();
        if (!$assignment_question){
            $this->message = "The question is not in the assignment.";
            return false;
        } else {
            $points = $assignment_question->points;
        }

        $points = Helper::removeZerosAfterDecimal($points);
        $this->message = "The question is only worth $points points.";
        return $value <= $points;
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
