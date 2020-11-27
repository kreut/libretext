<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class IsValidLetterGrades implements Rule
{
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
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        //server side version of javascript checks
        $letter_grades_array = explode(',',$value);
        $used_letters = [];
        $used_cutoffs = [];
        //required and there should be an even number of them
        $at_least_one_zero = false;
        $is_valid = $letter_grades_array && (count($letter_grades_array) % 2 === 0);
            if ($is_valid){
                for ($i=0; $i< count($letter_grades_array)/2;$i++){
                    //numerical cutoffs
                    if (!is_numeric($letter_grades_array[2*$i])) {
                        $is_valid = false;
                        break;
                    }
                    if ((int) $letter_grades_array[2*$i] === 0) {
                        $at_least_one_zero  = true;
                    }
                    //should be positive
                    if ($letter_grades_array[2*$i]<0) {
                        $is_valid = false;
                        break;
                    }
                    //don't repeat letters
                    if (in_array($letter_grades_array[2*$i+1], $used_letters)){
                        $is_valid = false;
                        break;
                    } else {
                        $used_letters[] = $letter_grades_array[2*$i+1];
                    }
                    //don't repeat numbers
                    if (in_array($letter_grades_array[2*$i], $used_cutoffs)){
                        $is_valid = false;
                        break;
                    } else {
                        $used_cutoffs[] = $letter_grades_array[2*$i];
                    }
                }
            }
            if (!$at_least_one_zero) {
                $is_valid = false;
            }
            return $is_valid;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'This should be a comma separated list of numerical cutoffs with associated letters such as "90,A,80,B".  At least one cutoff should be 0; every other cutoff should be positive.  And, each letter grade and corresponding cutoff should be used only once.';
    }
}
