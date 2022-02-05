<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class IsValidAssignmentTopic implements Rule
{
    private $assignment_id;
    private $topic_id;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($assignment_id, $topic_id)
    {
        $this->assignment_id = $assignment_id;
        $this->topic_id = $topic_id;
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

        $value = trim($value);
        $topic_exists = DB::table('assignment_topics')
            ->where('name', $value)
            ->where('assignment_id', $this->assignment_id)
            ->where('id', '<>', $this->topic_id)
            ->first();
        if ($topic_exists) {
            $this->message = "You already have a topic with that name within the assignment.";
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
