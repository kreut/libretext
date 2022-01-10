<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class IsValidSavedQuestionsFolder implements Rule
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
    public function __construct($type,$folder_id)
    {
        $this->folder_id = $folder_id;
        $this->type = $type;
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

        $value = trim($value);

        if (!$value) {
            $this->message = "This value must not be empty.";
            return false;
        }
        $folder_exists = $this->folder_id
            ? DB::table('saved_questions_folders')
                ->where('name', $value)
                ->where('type', $this->type)
                ->where('id', '<>', $this->folder_id)
                ->where('user_id', auth()->user()->id)
                ->exists()
            : DB::table('saved_questions_folders')
                ->where('name', $value)
                ->where('type', $this->type)
                ->where('user_id', auth()->user()->id)
                ->exists();
        if ($folder_exists) {
            $this->message = "You already have a folder with that name.";
            return false;
        }
        return true;
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
