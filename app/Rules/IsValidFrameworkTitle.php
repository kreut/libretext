<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class IsValidFrameworkTitle implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($user_id, $framework_id)
    {
        $this->user_id = $user_id;
        $this->title = '';
        $this->framework_id = $framework_id;
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
        $this->title = $value;
        $query = DB::table('frameworks')
            ->where('user_id', $this->user_id)
            ->where(DB::raw("LOWER(title)"), strtolower($value));

        if ($this->framework_id) {
            $query = $query->where('id', '<>', $this->framework_id);
        }
        return $query->count() === 0;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return "You already have a framework with the title '$this->title'.";
    }
}
