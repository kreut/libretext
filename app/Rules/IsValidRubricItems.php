<?php

namespace App\Rules;

use Exception;
use Illuminate\Contracts\Validation\Rule;

class IsValidRubricItems implements Rule
{
    /**
     * @var array
     */
    private $message;


    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     * @throws Exception
     */
    public function passes($attribute, $value)
    {
        $this->message = [];
        $passes = true;
        foreach ($value as $key => $item) {
            $this->message[$key] = ['title' => 'passes', 'points' => 'passes'];
        }
        $criteria = [];
        foreach ($value as $key => $item) {
            if (!$item['title']) {
                $this->message[$key]['title'] = 'A title is required.';
                $passes = false;
            } else {
                if (in_array(strtolower($item['title']), $criteria)) {
                    $this->message[$key]['title'] = 'This title already exists.';
                    $passes = false;
                }
                $criteria[] = strtolower($item['title']);
            }

            if (!$item['points']
                || $item['points'] < 0
                || filter_var($item['points'], FILTER_VALIDATE_FLOAT) === false) {
                $passes = false;
                $this->message[$key]['points'] = "The points should be a number that is at least 0.";
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
        return json_encode($this->message);
    }
}
