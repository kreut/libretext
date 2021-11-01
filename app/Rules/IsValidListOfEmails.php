<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class IsValidListOfEmails implements Rule
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
     * @param $whitelisted_instructor_emails
     * @return bool
     */
    public function passes($attribute, $whitelisted_instructor_emails)
    {

        $whitelisted_instructor_emails = explode(',',$whitelisted_instructor_emails);
        foreach ($whitelisted_instructor_emails as $email){
            if (!filter_var(trim($email), FILTER_VALIDATE_EMAIL)) {
                $this->message = "$email is not a valid email.";
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
        return $this->message;
    }
}
