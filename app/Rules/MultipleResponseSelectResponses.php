<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class MultipleResponseSelectResponses implements Rule
{
    private $responses;
    /**
     * @var string
     */
    private $message;
    private $number_to_select;
    private $question_type;


    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($question_type,  $responses, $number_to_select)
    {
        $this->responses = $responses;
        $this->question_type = $question_type;
        if ( $this->question_type === 'multiple_response_select_n') {
            $this->number_to_select = $number_to_select;
        }
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

        $number_correct_responses = 0;

        foreach ($this->responses as $response) {
            $number_correct_responses += +$response['correctResponse'];
            if (!$response['value']) {
                $passes = false;
                $message['specific'][$response['identifier']] = 'Text is required.';
            }
        }
        if ($this->question_type === 'multiple_response_select_n' ) {
            if ((int)$this->number_to_select !== $number_correct_responses) {
                $passes = false;
                $message['general'] = "The number of correct responses as determined by the prompt ($this->number_to_select) is not equal to the number of correct responses ($number_correct_responses).";
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
