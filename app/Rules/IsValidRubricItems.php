<?php

namespace App\Rules;

use Cassandra\Numeric;
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
     */
    public function passes($attribute, $value)
    {
        $this->message = [];
        $passes = true;
        foreach ($value as $key => $item) {
            $this->message[$key] = ['criterion' => 'passes', 'points' => 'passes'];
        }
        $criteria = [];
        foreach ($value as $key => $item) {
            if (!$item['criterion']) {
                $this->message[$key]['criterion'] = 'A criterion is required.';
                $passes = false;
            } else {
                if (in_array(strtolower($item['criterion']), $criteria)) {
                    $this->message[$key]['criterion'] = 'This criterion already exists';
                    $passes = false;
                }
                $criteria[] = strtolower($item['criterion']);
            }
            if (!$item['points']
                || $item['points'] < 0
                || filter_var($item['points'],FILTER_VALIDATE_FLOAT) === false) {
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
