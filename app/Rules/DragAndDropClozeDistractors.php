<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class DragAndDropClozeDistractors implements Rule
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
     * @param mixed $distractors
     * @return bool
     */
    public function passes($attribute, $distractors)
    {
        $passes = true;
        $message = [];
        foreach ($distractors as $distractor) {
            if (!$distractor['value']) {
                $passes = false;
                $message['specific'][$distractor['identifier']] = 'Text is required.';
            }
        }
        if ($message) {
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
