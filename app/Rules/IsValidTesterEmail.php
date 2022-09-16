<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class IsValidTesterEmail implements Rule
{
    private $course_id;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($course_id)
    {
        $this->course_id = $course_id;
        $this->message = '';
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
        $user = DB::table('users')->where('email', $value)->first();
        if (!$user || $user->role !== 6) {
            $this->message = "There is no tester with that email.";
            return false;
        }

        $exists_in_course = DB::table('tester_courses')
            ->where('user_id', $user->id)
            ->where('course_id', $this->course_id)
            ->first();
        if ($exists_in_course) {
            $this->message = "That tester is already linked up with this course.";
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
