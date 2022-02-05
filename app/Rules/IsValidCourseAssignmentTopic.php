<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IsValidCourseAssignmentTopic implements Rule
{
    private $course_id;
    private $assignment;
    private $topic;
    /**
     * @var string
     */
    private $message;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($course_id, $assignment, $topic)
    {
        $this->course_id = $course_id;
        $this->assignment = $assignment;
        $this->topic = $topic;
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
        if ($this->topic) {
            $this->message = "You do not own that combination of Course, Assignment, Topic.";
            return DB::table('courses')
                ->join('assignments', 'courses.id', '=', 'assignments.course_id')
                ->join('assignment_topics', 'assignments.id', '=', 'assignment_topics.assignment_id')
                ->where('courses.id', $this->course_id)
                ->where('assignments.name', $this->assignment)
                ->where('assignment_topics.name', $this->topic)
                ->where('courses.user_id', Auth::user()->id)
                ->exists();

        } else {
            $this->message = "You do not own that combination of Course and Assignment.";
           return  DB::table('courses')
                ->join('assignments', 'courses.id', '=', 'assignments.course_id')
                ->where('courses.id', $this->course_id)
                ->where('assignments.name', $this->assignment)
                ->where('courses.user_id', Auth::user()->id)
                ->exists();
        }
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
