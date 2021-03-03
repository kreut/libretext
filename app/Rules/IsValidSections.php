<?php

namespace App\Rules;

use App\Course;
use Illuminate\Contracts\Validation\Rule;

class IsValidSections implements Rule
{
    /**
     * IsValidSections constructor.
     * @param int $course_id
     */
    public function __construct(int $course_id)
    {
        $this->course_id = $course_id;
    }

    /**
     * @param string $attribute
     * @param mixed $selectedSections
     * @return bool
     */
    public function passes($attribute, $selectedSections)
    {

        if (!$selectedSections){
            return false;
        }
        $course_sections = Course::find($this->course_id)->sections->pluck('id')->toArray();
        foreach ($selectedSections as $section){
            if (!in_array($section,$course_sections)){
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
        return 'Please include at least one section from the course.';
    }
}
