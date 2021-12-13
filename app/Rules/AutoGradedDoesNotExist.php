<?php

namespace App\Rules;

use App\Question;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class AutoGradedDoesNotExist implements Rule
{
    /**
     * @var string
     */
    private $technology;
    /**
     * @var mixed
     */
    private $technology_id;
    private $question_id;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(string $technology, $question_id)
    {
        $this->technology = $technology;
        $this->question_id = $question_id;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        $this->technology_id = $value;

        switch ($this->technology) {
            case('h5p'):
                $like = "%https://studio.libretexts.org/h5p/$this->technology_id/embed%";

                break;
            case('webwork'):
                $like = "%;sourceFilePath=$this->technology_id%";
                break;
            case('imathas'):
                $like = "%?id=$this->technology_id%";
                break;
            default:
                $this->message = "Not a valid technology.";
                return false;
        }

        return $this->question_id
            ? DB::table('questions')
                ->where('technology', 'like', $like)
                ->where('technology_iframe', 'like', $like)
                ->where('id', '<>', $this->question_id)
                ->doesntExist()
            : DB::table('questions')
                ->where('technology_iframe', 'like', $like)
                ->doesntExist();

    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        $id = 'ID';
        switch ($this->technology) {
            case('h5p'):
                $formatted_technology = 'H5P';
                break;
            case('webwork'):
                $formatted_technology = 'WeBWork';
                $id = 'File Path';
                break;
            case('imathas'):
                $formatted_technology = 'IMathAS';

                break;
            default:
                $formatted_technology = $this->technology;
        }
        return "A $formatted_technology question with $id $this->technology_id already exists in the database.";
    }
}
