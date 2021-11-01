<?php

namespace App\Rules;

use App\User;
use Illuminate\Contracts\Validation\Rule;

class IsValidInstructorAccessCodeEmail implements Rule
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
        $this->message = '';
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $email)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->message = "Not a valid email";
            return false;
        }

        if (User::where('email', $email)->exists()) {
            $this->message = "A registered user already has this email.";
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
