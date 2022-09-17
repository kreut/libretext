<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class BowTieItems implements Rule
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
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {

        $passes = true;
        $num_distractors = 0;
        $message = [];
        $items = [];
        foreach ($value as $item) {
            if (!$item['value']) {
                $passes = false;
                $message['specific'][$item['identifier']] = "Text is required.";
            } else {
                $num_distractors += +!$item['correctResponse'];
                if (in_array($item['value'], $items)) {
                    $passes = false;
                    $message['specific'][$item['identifier']] = "{$item['value']} appears multiple times within the group.";
                }
                $items[] = $item['value'];

            }
        }
        if (!$num_distractors) {
            $passes = false;
            $message['general'] = "There should be at least one distractor.";
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
    public function message(): string
    {
        return $this->message;
    }
}
