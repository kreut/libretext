<?php

namespace App\Rules;

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
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {

       if  ($value === '') {
           $this->message = 'Your QTI question is missing a Prompt.';
           return false;
       }
       $question_exists = DB::table('questions')
           ->where('qti_json','like',"%{$value}%")
           ->first();
       if ($question_exists){
           $this->message = "QTI question $question_exists->id already exists in the database.";
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
