<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class IsValidDropDownTriadCauseAndEffects implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($qti_array, $identifier)
    {
        $this->qti_array = $qti_array;
        $this->identifier = $identifier;
        $this->message = [];
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
        ///valid drop down: condition needs one correct and 2-4 incorrect
        /// rationales needs 2 correct and 1-3 incorrect
        $message = [];
        if (count($this->qti_array) < 3 || count($this->qti_array) > 5) {
            $items = $this->identifier === 'condition' ? 'conditions' : 'rationales';
            $message[$this->identifier]['general'][] = "There should be between 3 and 5 $items.";
        }
        $items = [];
        foreach ($this->qti_array as $item) {
            if (!$item['text']) {
                $message[$this->identifier]['specific'][$item['value']][] = "This field is required.";
            } else {
                if (in_array($item['text'], $items)) {
                    $message[$this->identifier]['specific'][$item['value']][] = "{$item['text']} appears more than once.";
                }
                $items[] = $item['text'];
            }
        }
        if ($message) {
            $this->message = json_encode($message);
        }
        return !$this->message;
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
