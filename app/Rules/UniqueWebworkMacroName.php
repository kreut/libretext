<?php

namespace App\Rules;

use App\WebworkMacro;
use Illuminate\Contracts\Validation\Rule;

class UniqueWebworkMacroName implements Rule
{

    protected $message = '';

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($ignore_id = null)
    {
        $this->ignore_id = $ignore_id;
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
        // Check for active macro with this name
        if (WebworkMacro::where('name', $value)
            ->where('is_retired', false)
            ->when($this->ignore_id, function($q) {
                $q->where('id', '!=', $this->ignore_id);
            })
            ->exists()) {
            $this->message = "A macro named {$value} already exists.";
            return false;
        }

        // Check for retired macro with this name
        if (WebworkMacro::where('name', $value)
            ->where('is_retired', true)
            ->when($this->ignore_id, function($q) {
                $q->where('id', '!=', $this->ignore_id);
            })
            ->exists()) {
            $this->message = "A macro named {$value} was previously retired. Please choose a different name.";
            return false;
        }

        return true;
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
