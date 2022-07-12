<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class IsValidLearningOutcomes implements Rule
{
    private $learning_outcomes;
    /**
     * @var string
     */
    private $message;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($learning_outcomes)
    {
        $this->learning_outcomes = $learning_outcomes;
        $this->message= '';
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
       foreach ( $this->learning_outcomes as $learning_outcome){
          $learning_outcome_id = is_array($learning_outcome) ? $learning_outcome['id'] : $learning_outcome;
           if (!DB::table('learning_outcomes')->where('id', $learning_outcome_id)->first()) {
               $this->message = "The learning outcome with ID $learning_outcome_id does not appear in the database.";
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
    public function message(): string
    {
        return $this->message;
    }
}
