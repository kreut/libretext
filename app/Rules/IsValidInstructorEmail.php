<?php

namespace App\Rules;

use App\User;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class IsValidInstructorEmail implements Rule
{
    private $email;
    /**
     * @var string
     */
    private $message;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($email)
    {
        $this->email = $email;
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
        $user_by_email = User::where('role', 2)->where('email', $value)->first();
        if (!$user_by_email) {
            $passes = false;
            $this->message = "There are no instructors with that email.";
        } else
        if ($user_by_email->email === Auth::user()->email) {
            $passes = false;
            $this->message = "You can't invite yourself to be a co-instructor.";
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
