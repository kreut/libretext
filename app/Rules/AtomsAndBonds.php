<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class AtomsAndBonds implements Rule
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
    public function passes($attribute, $value)
    {
        $message = ['specific' => [], 'general' => ''];
        $total_points = 0;
        $passes = true;
        foreach ($value as $atom_bond) {
            $specific_message = ['index' => $atom_bond['index'], 'correct' => '', 'incorrect' => ''];
            if (!is_numeric($atom_bond['correct']) || $atom_bond['correct'] < 0 || $atom_bond['correct'] > 100) {
                $specific_message['correct'] = "The % Added should be between 0 and 100.";
                $passes = false;
            }
            if (!is_numeric($atom_bond['incorrect']) || $atom_bond['incorrect'] < 0 || $atom_bond['incorrect'] > 100) {
                $specific_message['incorrect'] = "The % Removed should be between 0 and 100.";
                $passes = false;
            }

            $message['specific'][] = $specific_message;
            $total_points += floatval($atom_bond['correct']);
        }
        $total_points = round($total_points, 1);
        if ($total_points !== floatval(100)) {
            $message['general'] = "Your '% Added' points only sum to $total_points%; they should sum to 100%.";
            $passes = false;
        }
        $this->message = json_encode($message);
        return $passes;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public
    function message()
    {
        return $this->message;
    }
}
