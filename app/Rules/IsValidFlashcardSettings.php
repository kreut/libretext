<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class IsValidFlashcardSettings implements Rule
{
    private $message = 'The flashcard settings are invalid.';

    public function passes($attribute, $value): bool
    {
        $data = is_string($value) ? json_decode($value, true) : (array)$value;

        if (!is_array($data)) {
            $this->message = 'The flashcard settings must be a valid JSON object.';
            return false;
        }

        // Validate autoplay
        if (!isset($data['autoplay']) || !is_array($data['autoplay'])) {
            $this->message = 'The autoplay setting is missing.';
            return false;
        }
        if (!array_key_exists('enabled', $data['autoplay']) || !is_bool($data['autoplay']['enabled'])) {
            $this->message = 'The autoplay enabled field must be true or false.';
            return false;
        }
        if (!array_key_exists('student_override', $data['autoplay']) || !is_bool($data['autoplay']['student_override'])) {
            $this->message = 'The autoplay student override field must be true or false.';
            return false;
        }
        if ($data['autoplay']['enabled']) {
            if (!isset($data['autoplay']['seconds']) || !is_numeric($data['autoplay']['seconds']) || (int)$data['autoplay']['seconds'] < 1 || (int)$data['autoplay']['seconds'] > 30) {
                $this->message = 'The autoplay seconds must be between 1 and 30.';
                return false;
            }
        }

        // Validate simple boolean settings
        $simpleSettings = ['random_shuffle', 'show_hint', 'text_to_speech', 'captions'];
        foreach ($simpleSettings as $setting) {
            if (!isset($data[$setting]) || !is_array($data[$setting])) {
                $this->message = "The {$setting} setting is missing.";
                return false;
            }
            if (!array_key_exists('enabled', $data[$setting]) || !is_bool($data[$setting]['enabled'])) {
                $this->message = "The {$setting} enabled field must be true or false.";
                return false;
            }
            if (!array_key_exists('student_override', $data[$setting]) || !is_bool($data[$setting]['student_override'])) {
                $this->message = "The {$setting} student override field must be true or false.";
                return false;
            }
        }

        return true;
    }

    public function message(): string
    {
        return $this->message;
    }
}
