<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class IMathAS extends Model
{
    /**
     * @param $question
     * @return bool
     */
    public function solutionExists($question): bool
    {
        if ($question->technology === 'imathas' && Cache::has('imathas_questions_with_solutions')){
           return in_array($question->technology_id, Cache::get('imathas_questions_with_solutions'));
        }
        return false;
    }

}
