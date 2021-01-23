<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class IsValidSolutionText implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($user_id, $question_id)
    {
        $this->user_id= $user_id;
        $this->question_id = $question_id;
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
        $audio_solution_exists =   DB::table('solutions')
            ->where('user_id', $this->user_id)
            ->where('question_id', $this->question_id)
            ->where('type', 'audio')
            ->first();

        return $audio_solution_exists && $value;

    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Your text must be non-empty and you should already have uploaded an audio file.';
    }
}
