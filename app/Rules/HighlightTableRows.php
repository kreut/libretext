<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class HighlightTableRows implements Rule
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
     * @param $attribute
     * @param $value
     * @return bool|void
     */
    public function passes($attribute, $value)
    {
        $passes = true;
        $message = [];
        foreach ($value as $row_key => $row) {
            if (!$row['header']) {
                $passes = false;
            }
            $message[$row_key]['header'] = !$row['header'] ? 'Text is required.' : '';
            $row_responses = [];
            if (!$row['responses']) {
                $passes = false;
                $message[$row_key]['responses']['text'] = 'Text is required.';
            }
            foreach ($row['responses'] as $response_key => $response) {
                $message[$row_key]['responses'][$response_key]['text'] = '';
                $message[$row_key]['responses'][$response_key]['correctResponse'] = '';
                if (!$response['text']) {
                    $message[$row_key]['responses'][$response_key]['text'] = 'Text is required.';
                    $passes = false;
                }

                if ($response['correctResponse'] === null) {
                    $passes = false;
                    $message[$row_key]['responses'][$response_key]['correctResponse'] = 'Please select one of the options.';
                }
                if ($response['text'] && in_array($response['text'], $row_responses)) {
                    $message[$row_key]['responses'][$response_key]['text'] = "{$response['text']} is repeated more than once.";
                }
                $row_responses[] = $response['text'];
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
