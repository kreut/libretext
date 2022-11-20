<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class MultipleResponseGroupingRows implements Rule
{
    private $rows;
    /**
     * @var string
     */
    private $message;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($rows)
    {
        $this->rows = $rows;
        $this->message = '';
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
        $passes = true;
        $message = [];

        if (!count($this->rows)) {
            $passes = false;
            $message['general'] = 'There should be at least 1 row.';
        }

        foreach ($this->rows as $key => $row) {
            if (!$row['grouping']) {
                $passes = false;
                $message['specific']['grouping'][$key] = 'Row header is required.';
            }
            if (count($row['responses']) === 0) {
                $passes = false;
                $group_message = 'This row has no responses.';
                $message['specific']['grouping'][$key] =
                    isset($message['specific']['grouping'][$key])
                        ? $message['specific']['grouping'][$key] . '  ' . $group_message
                        : $group_message;
            }
            $at_least_one_selected = false;
            $responses = [];
            foreach ($row['responses'] as $response_key => $response) {
                if (isset($response['correctResponse']) && $response['correctResponse']) {
                    $at_least_one_selected = true;
                }
                if (!$response['value']) {
                    $passes = false;
                    $message['specific'][$key]['value'][$response_key] = 'Text is required.';
                } else {
                    if (in_array($response['value'], $responses)) {
                        $passes = false;
                        $message['specific'][$key]['value'][$response_key] = "The response `{$response['value']}` appears multiple times within the grouping.";
                    }
                    $responses[] = $response['value'];
                }
            }
            if (!$at_least_one_selected) {
                $passes = false;
                $message['specific'][$key]['at_least_one_correct'] = 'At least one in the group must be marked correct.';
            }
            if (count($row['responses']) === 1) {
                $passes = false;
                $message['specific'][$key]['at_least_two_responses'] = 'There should be at least two responses.';
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
    public function message(): string
    {
        return $this->message;
    }
}
