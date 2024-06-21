<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FormattedQuestionType extends Model
{
    protected $guarded = [];

    /**
     * @return Collection
     */
    public function allFormattedQuestionTypes(): Collection
    {
        return DB::table('formatted_question_type_technology')->orderBy('formatted_question_type')->get();

    }
}
