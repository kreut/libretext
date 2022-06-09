<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class nonRepeatingMatchingTerms implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($qti_array)
    {
        $this->qti_array = $qti_array;
        $this->message = '';
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
        $used_matching_terms = [];
        foreach ($this->qti_array['possibleMatches'] as $value) {
            if (!in_array( $value['matchingTerm'],$used_matching_terms)) {
                if ($value['matchingTerm']) {
                    $used_matching_terms[] = $value['matchingTerm'];
                }
            } else {
                $this->message = "{$value['matchingTerm']} appears multiple times as a matching term.";
                $passes = false;
            }
        }
        return $passes;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return $this->message;
    }
}
