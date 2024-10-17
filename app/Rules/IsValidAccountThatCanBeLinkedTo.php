<?php

namespace App\Rules;

use App\User;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class IsValidAccountThatCanBeLinkedTo implements Rule
{
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
        //
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
        $this->message = '';
        if (!User::where('email', $value)
            ->where('role', 2)
            ->whereNotIn('id', [1, 5])
            ->exists()
        ) {
            $passes = false;
            $this->message = "That is not a valid instructor account.";
        }
        if ($value === Auth::user()->email) {
            $passes = false;
            $this->message = "You cannot link to your own account.";
        }
        return $passes;
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
