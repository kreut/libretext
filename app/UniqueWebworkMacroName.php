<?php

namespace App\Rules;

use App\WebworkMacro;
use Illuminate\Contracts\Validation\Rule;

class UniqueWebworkMacroName implements Rule
{
    protected $ignore_id;
    protected $blocked_by_official = false;

    public function __construct($ignore_id = null)
    {
        $this->ignore_id = $ignore_id;
    }

    public function passes($attribute, $value)
    {
        // Block if an official macro with this name exists — can never be overridden
        $official = WebworkMacro::where('source', 'official')
            ->whereRaw('LOWER(name) = LOWER(?)', [$value])
            ->exists();

        if ($official) {
            $this->blocked_by_official = true;
            return false;
        }

        // Block duplicate custom macro names (case-insensitive), ignoring self on edit
        $query = WebworkMacro::where('source', 'custom')
            ->whereRaw('LOWER(name) = LOWER(?)', [$value]);

        if ($this->ignore_id) {
            $query->where('id', '<>', $this->ignore_id);
        }

        return !$query->exists();
    }

    public function message()
    {
        if ($this->blocked_by_official) {
            return 'A macro with this name already exists as an official macro and cannot be overridden.';
        }
        return 'A macro with this name already exists in the system (names are case-insensitive).';
    }
}
