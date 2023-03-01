<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class Formative implements Rule
{
    /**
     * @var string
     */
    private $message;
    private $course;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($course)
    {
        $this->course = $course;
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
        $this->message = '';
        $passes = true;
        if ($value === $this->course->formative) {
            return true;
        }

        if (DB::table('beta_courses')->where('alpha_course_id', $this->course->id)->first()) {
            $passes = false;
            $this->message = "You cannot switch the formative nature of the course since it is an Alpha course with at least one tethered Beta course.";
        }
        if (DB::table('beta_courses')->where('id', $this->course->id)->first()) {
            $passes = false;
            $this->message = "You cannot switch the formative nature of the course since it is a Beta course.";
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
