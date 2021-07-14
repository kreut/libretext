<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class ConfirmedBetaCourseName implements Rule
{
    private $course_id;
    private $name;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($course_id, $name)
    {
        $this->course_id = $course_id;
        $this->name = $name;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Query\Builder|object|null
     */
    public function passes($attribute, $value)
    {
        return DB::table('courses')
            ->where('id', $this->course_id)
            ->where('name', $this->name)
            ->first();

    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return "$this->name is not the name of your course.";
    }
}
