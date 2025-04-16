<?php

namespace App\Rules;

use Cassandra\Numeric;
use Exception;
use Illuminate\Contracts\Validation\Rule;

class IsValidRubricItems implements Rule
{
    /**
     * @var array
     */
    private $message;
    private $score_input_type;

    public function __construct($score_input_type)
    {
        $this->score_input_type = $score_input_type;
    }

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
            $this->message[$key] = ['title' => 'passes', 'points' => 'passes', 'percentage' => 'passes'];
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
            switch ($this->score_input_type) {
                case('points'):
                    if (!$item['points']
                        || $item['points'] < 0
                        || filter_var($item['points'], FILTER_VALIDATE_FLOAT) === false) {
                        $passes = false;
                        $this->message[$key]['points'] = "The points should be a number that is at least 0.";
                    }
                    break;
                case('percentage'):
                    if (!$item['percentage']
                        || $item['percentage'] < 0
                        || $item['percentage'] > 100
                        || filter_var($item['percentage'], FILTER_VALIDATE_FLOAT) === false) {
                        $passes = false;
                        $this->message[$key]['percentage'] = "The percentages should be between 0 and 100.";
                    }
                    break;
                default:
                    throw new Exception ("The score input type '$this->score_input_type' is not valid.");
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
