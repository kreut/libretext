<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class IsValidFrameworkItemSyncQuestion implements Rule
{
    /**
     * @var string
     */
    private $message;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->message = '';
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
        $descriptors = $value['descriptors'] ?? [];
        $levels = $value['levels'] ?? [];
        $passes = true;
        foreach ($descriptors as $descriptor) {
            if (!DB::table('framework_descriptors')
                ->where('id', $descriptor['id'])
                ->where('descriptor', $descriptor['text'])
                ->first()) {
                $this->message .= "{$descriptor['text']} is not a valid descriptor.  ";
                $passes = false;
            }
        }
        foreach ($levels as $level) {
            if (!DB::table('framework_levels')
                ->where('id', $level['id'])
                ->where('description', $level['text'])
                ->first()) {
                $this->message .= "{$level['text']} is not a valid framework level.  ";
                $passes = false;
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
        return $this->message;
    }
}
