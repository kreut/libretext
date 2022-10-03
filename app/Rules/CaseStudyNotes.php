<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class CaseStudyNotes implements Rule
{
    /**
     * @var false|string
     */
    private $message;

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
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        $passes = true;
        $message = [];
        $case_study_notes = json_decode($value, 1);
        foreach ($case_study_notes as $item) {
            if (!$item['notes']) {
                $passes = false;
                $message[$item['type']] = 'Text is required.';
            }
        }
        if ($message){
            $this->message = json_encode($message);
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
