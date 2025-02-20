<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class IsValidMinNumberForDiscussIt implements Rule
{
    private $min_number_of_initiated_discussion_threads;
    private $min_number_of_initiate_or_reply_in_threads;
    /**
     * @var string
     */
    private $message;


    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($min_number_of_initiated_discussion_threads, $min_number_of_initiate_or_reply_in_threads)
    {
        $this->min_number_of_initiated_discussion_threads = $min_number_of_initiated_discussion_threads;
        $this->min_number_of_initiate_or_reply_in_threads = $min_number_of_initiate_or_reply_in_threads;
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
        $options = ["options" => ["min_range" => 0]];
        if ((filter_var($this->min_number_of_initiated_discussion_threads, FILTER_VALIDATE_INT, $options) === false)
            || (filter_var($this->min_number_of_initiate_or_reply_in_threads, FILTER_VALIDATE_INT, $options) === false)
        ) {
            return true;
        }
        $this->message = '';

        if (filter_var($value, FILTER_VALIDATE_INT, $options) === false) {
            $this->message = "The min number of submitted replies should be an integer greater than or equal to 0.";
            return false;
        }

        if ($value && $this->min_number_of_initiated_discussion_threads > $value) {
            $this->message = "The min number of initiated discussion threads can't be greater than the number of submitted comments. Each time a student initiates a thread they are also submitting a comment.";
            return false;
        }

        if ($value && $this->min_number_of_initiate_or_reply_in_threads > $value) {
            $this->message = "The min number of threads that a student needs to participate in (initiate or reply) shouldn't be more than the number of comments that they need to submit.";
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
