<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class RubricCategories implements Rule
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
        $message = [];
        $passes = true;
        $total_percent = 0;
        $categories = [];
        foreach ($value as $rubric_category) {
            $total_percent += $rubric_category['percent'];
            if (!$rubric_category['category'] && $passes) {
                $passes = false;
                $message['category'] = "Every category needs a name.";
            } else {
                if (in_array($rubric_category['category'], $categories)) {
                    $message['category'] = "{$rubric_category['category']} is repeated multiple times.";
                    $passes = false;
                }
                $categories[] = $rubric_category['category'];
            }
            if (!$rubric_category['criteria']) {
                $passes = false;
                $message['criteria'] = "Every category needs a criteria.";
            }
        }
        if ($total_percent !== 100) {
            $passes = false;
            $message['percent'] = "The percents do not sum to 100.";
        }

        $this->message = json_encode($message);
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
