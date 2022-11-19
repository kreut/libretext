<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;
use function GuzzleHttp\Psr7\str;

class IsValidNewFrameworkLevel implements Rule
{
    private $framework_id;
    private $level_to_add;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($framework_id, $level_to_add)
    {
        $this->framework_id = $framework_id;
        $this->level_to_add = $level_to_add;
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
        return DB::table('framework_levels')
                ->where('framework_id', $this->framework_id)
                ->where('level', $this->level_to_add)
                ->where(DB::raw("LOWER(title)"), strtolower($value))
                ->count() === 0;

    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return "There is already a Level $this->level_to_add with the same description.";
    }
}
