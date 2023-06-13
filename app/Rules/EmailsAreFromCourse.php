<?php

namespace App\Rules;

use App\Course;
use Illuminate\Contracts\Validation\Rule;

class EmailsAreFromCourse implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($course_id)
    {
        $this->course_id = $course_id;
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
        $passes = true;
        $enrolled_user_emails = [];
        $enrolled_users = Course::find($this->course_id)->enrolledUsers;
        foreach ($enrolled_users as $enrolled_user) {
            $enrolled_user_emails[] = $enrolled_user->email;
        }
        foreach ($value as $email) {
            if (!in_array($email, $enrolled_user_emails)) {
                $passes = false;
                $this->message = "$email does not belong to an enrolled user in your course.";
            }
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
