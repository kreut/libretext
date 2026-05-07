<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ValidNumericalPlaceholders implements Rule
{
    private string $message = '';

    public function passes($attribute, $value): bool
    {
        $placeholders = is_string($value) ? json_decode($value, true) : $value;

        $passes  = true;
        $message = [];

        if (!is_array($placeholders) || empty($placeholders)) {
            $message['general'] = 'At least one underlined blank is required in the prompt.';
            $this->message = json_encode($message);
            return false;
        }

        foreach ($placeholders as $i => $placeholder) {
            $toleranceType = $placeholder['toleranceType'] ?? 'absolute';
            $blankValue    = $placeholder['value'] ?? '';

            if ($blankValue === '' || !is_numeric($blankValue)) {
                $passes           = false;
                $message[$i]['value'] = "The underlined text must be a number.";
            }

            if ($toleranceType === 'relative') {
                $tol = $placeholder['relativeTolerance'] ?? '';
                if ($tol === '' || !is_numeric($tol)) {
                    $passes                          = false;
                    $message[$i]['relativeTolerance'] = 'Relative tolerance must be a number.';
                } elseif ((float) $tol < 0) {
                    $passes                          = false;
                    $message[$i]['relativeTolerance'] = 'Relative tolerance cannot be negative.';
                }
            } else {
                $tol = $placeholder['absoluteTolerance'] ?? '';
                if ($tol === '' || !is_numeric($tol)) {
                    $passes                          = false;
                    $message[$i]['absoluteTolerance'] = 'Absolute tolerance must be a number.';
                } elseif ((float) $tol < 0) {
                    $passes                          = false;
                    $message[$i]['absoluteTolerance'] = 'Absolute tolerance cannot be negative.';
                }
            }
        }

        if ($message) {
            $this->message = json_encode($message);
        }

        return $passes;
    }

    public function message(): string
    {
        return $this->message;
    }
}
