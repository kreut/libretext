<?php

namespace App\Rules;

use App\Course;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class IsValidCourseForBulkUpload implements Rule
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
     * @param $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $course = Course::find($value);

        if ($course) {
            $this->message = $course->bulkUploadAllowed();
            if ($this->message) {
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
