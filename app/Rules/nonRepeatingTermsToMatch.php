<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class nonRepeatingTermsToMatch implements Rule
{
    private $qti_array;
    /**
     * @var string
     */
    private $message;

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
        $used_terms_to_match = [];
        foreach ($this->qti_array['termsToMatch'] as $value) {
            if (!in_array($value['termToMatch'],$used_terms_to_match)) {
                if ( $value['termToMatch']) {
                    $used_terms_to_match[] = $value['termToMatch'];
                }
            } else {
                $this->message = "{$value['termToMatch']} appears multiple times as a term to match.";
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
