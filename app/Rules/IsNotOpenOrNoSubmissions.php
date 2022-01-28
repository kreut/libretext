<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class IsNotOpenOrNoSubmissions implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($assignment_id)
    {
        $this->assignment_id = $assignment_id;
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

        $assign_to_timings = DB::table('assign_to_timings')
            ->where('assignment_id', $this->assignment_id)
            ->get();
        foreach ($assign_to_timings as $assign_to_timing) {
            if (strtotime($assign_to_timing->available_from) < time() && strtotime($assign_to_timing->due) > time()) {
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
    public function message()
    {
        return "The total points can't be changed while the assignment is open.";
    }
}
