<?php

namespace App\Rules;

use App\LearningTree;
use Illuminate\Contracts\Validation\Rule;

class IsValidLearningTreeIds implements Rule
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
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $value_without_spaces = str_replace(' ','', $value);
        if (!$value_without_spaces){
            $this->message = "You need at least one Learning Tree.";
            return false;
        }
        $learning_tree_ids = explode(',',$value_without_spaces);
        foreach ($learning_tree_ids as $learning_tree_id){
           if (!LearningTree::where('id', $learning_tree_id)->first()){
               $this->message = "$learning_tree_id is not a valid learning tree id.";
               return false;
           }
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
