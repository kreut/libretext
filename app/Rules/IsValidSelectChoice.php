<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class IsValidSelectChoice implements Rule
{
    private $qti_array;
    private $identifier;
    private $message;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($qti_array, $identifier)
    {
        $this->qti_array = $qti_array;
        $this->identifier = $identifier;
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

        $choices = $this->qti_array['inline_choice_interactions'][$this->identifier];
        if (count($choices) < 2) {
            $this->message .= "The identifier [$this->identifier] should have at least 2 choices.<br>";
        }

        foreach ($choices as $choice) {
            if ($choice['text'] === '') {
                $this->message .= "The identifier [$this->identifier] has a blank choice.<br>";
            }
        }
        $used_choices = [];
        foreach ($choices as $choice) {
            if (in_array($choice['text'], $used_choices)) {
                $this->message .= "The choice '{$choice['text']}' appears multiple times under the identifier [$this->identifier].<br>";
                break;
            } else {
                $used_choices[] = $choice['text'];
            }

        }

        $used_identifiers = [];
        $identifiers = array_keys($this->qti_array['inline_choice_interactions']);
        foreach ($identifiers as $identifier) {
            if (in_array($identifier, $used_identifiers)) {
                $this->message .= "The identifier [$identifier] appears multiple times.<br>";
                break;
            } else {
                $used_identifiers[] = $identifier;
            }

        }
        return !$this->message;

    }

    /**
     * Get the validation error message.
     *.<br>
     * @return string
     */
    public function message()
    {
        return $this->message;
    }
}
