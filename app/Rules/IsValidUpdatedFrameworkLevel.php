<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class IsValidUpdatedFrameworkLevel implements Rule
{
    private $framework_level_id;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($framework_level_id)
    {
        $this->framework_level_id = $framework_level_id;
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
        $framework_level = DB::table('framework_levels')
            ->where('id', $this->framework_level_id)
            ->first();
        return DB::table('framework_levels')
                ->where('id', '<>', $framework_level->id)
                ->where('level', $framework_level->level)
                ->where('parent_id', $framework_level->parent_id)
                ->where(DB::raw("LOWER(title)"), strtolower($value))
                ->count() === 0;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Titles at a given level must be unique.';
    }
}
