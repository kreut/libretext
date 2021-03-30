<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class HasNoRandomizedAssignmentQuestions implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($assignment_id)
    {
        $this->assignment_id = $assignment_id;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
       $is_empty=  DB::table('randomized_assignment_questions')
            ->join('users', 'randomized_assignment_questions.user_id','=', 'users.id')
            ->where('users.fake_student',0)
            ->get()
            ->isEmpty();
       if ($is_empty){
           //good to go, let's remove the fake students who are there...
           DB::table('submissions')
               ->where('assignment_id', $this->assignment_id)
               ->delete();
           DB::table('submission_files')
               ->where('assignment_id', $this->assignment_id)
               ->delete();
           DB::table('randomized_assignment_questions')
               ->where('assignment_id', $this->assignment_id)
               ->delete();
           DB::table('scores')
               ->where('assignment_id', $this->assignment_id)
               ->delete();
       }
       return $is_empty;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'You cannot update the number of randomized assessments since we have created a randomized assignment for at least one student.';
    }
}
