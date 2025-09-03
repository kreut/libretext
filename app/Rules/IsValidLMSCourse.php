<?php

namespace App\Rules;

use App\Course;
use App\LmsAPI;
use Exception;
use Illuminate\Contracts\Validation\Rule;

class IsValidLMSCourse implements Rule
{
    /**
     * @var mixed
     */
    private $lmsErrorMessage;
    /**
     * @var int
     */
    private $course_id;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(int $course_id)
    {
        $this->course_id = $course_id;
        $this->lmsErrorMessage = '';
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     * @throws Exception
     */
    public function passes($attribute, $value): bool
    {
        if (app()->environment('testing')){
            return true;
        }
        $course = Course::find($this->course_id);
        $lms_api = new LmsAPI();
        $result = $lms_api->getCourse($course->getLtiRegistration(), $course->user_id, $value);
        if ($result['type'] === 'error') {
            $this->lmsErrorMessage = $result['message'];
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
        return "Error retrieving the course from your LMS: $this->lmsErrorMessage";
    }
}
