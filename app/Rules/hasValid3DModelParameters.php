<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class hasValid3DModelParameters implements Rule
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
        $errors = [];
        $this->message = '';
        if (!filter_var($value['modelID'], FILTER_VALIDATE_URL)) {
            $errors['modelID'] = "The modelID is not a valid URL.";
        }
        if ($value['BGImage'] && !filter_var($value['BGImage'], FILTER_VALIDATE_URL)) {
            $errors['BGImage'] = "The BGImage is not a valid URL.";
        }
        if (!$value['modelID'] && !$value['BGImage']){
            $errors['modelID'] = "Either the modelID or the BGImage is required.";
        }

        if ($value['annotations']) {
            if (!filter_var($value['annotations'], FILTER_VALIDATE_URL)) {
                $errors['annotations'] = "The annotations is not a valid URL.";
            }
            if (!$value['modelID']){
                $errors['modelID'] = "A modelID is required if you also have annotations.";
            }
        }
        if (!in_array($value['mode'], ['jigsaw', 'selection'])) {
            $errors['mode'] = "{$value['mode']} is not a valid mode.";
        }
        if ($value['BGColor']) {
            $value['BGColor'] = str_replace('#', '', $value['BGColor']);
            if (!ctype_xdigit($value['BGColor'])) {
                $errors['BGColor'] = "BGColor is not a valid hexadecimal.";
            }
        }
        if (!preg_match('/^$|^\d+,\d+,\d+$/', $value['modelOffset'])) {
            $errors['modelOffset'] = "modelOffset is not of the correct form (Ex. 0,0,2)";
        }
        if ($value['STLmatCol']) {
            $value['STLmatCol'] = str_replace('#', '', $value['STLmatCol']);
            if (!ctype_xdigit($value['STLmatCol'])) {
                $errors['STLmatCol'] = "STLmatCol is not a valid hexadecimal.";
            }
        }

        if ($value['hideDistance'] && (!is_numeric($value['hideDistance']) || $value['hideDistance'] < 0)) {
            $errors['hideDistance'] = "hideDistance should be either empty or a non-negative number.";
        }
        if ($errors) {
            $this->message = json_encode($errors);
        }
        return !$errors;
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
