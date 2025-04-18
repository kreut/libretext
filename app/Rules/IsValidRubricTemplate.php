<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class IsValidRubricTemplate implements Rule
{
    private $is_edit;
    private $rubric_template_id;
    private $name;
    private $user_id;
    /**
     * @var string
     */
    private $message;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($is_edit, $rubric_template_id, $name, $user_id)
    {
        $this->is_edit = $is_edit;
        $this->rubric_template_id = $rubric_template_id;
        $this->name = $name;
        $this->user_id = $user_id;
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
        $passes = true;
        $this->message = '';
        switch ($this->is_edit) {
            case(true):
                $passes = DB::table('rubric_templates')
                    ->where("id", $this->rubric_template_id)
                    ->where('user_id', $this->user_id)
                    ->exists();
                if (!$passes) {
                    $this->messsage = "That is not your rubric template so you can't update it.";
                } else {
                    $passes = !DB::table('rubric_templates')
                        ->where('user_id', $this->user_id)
                        ->whereRaw('LOWER(name) = ?', [strtolower($this->name)])
                        ->where('id', '<>', $this->rubric_template_id)
                        ->exists();
                    $this->message = "You already have a rubric template with the name $this->name.";
                }
                break;
            case(false):
                $passes = !DB::table('rubric_templates')
                    ->where('user_id', $this->user_id)
                    ->whereRaw('LOWER(name) = ?', [strtolower($this->name)])
                    ->exists();
                $this->message = "You already have a rubric template with the name $this->name.";
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
