<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class QuestionRevision extends Model
{
    protected $guarded = [];

    /**
     * @param array $question_ids
     * @return array
     */
    public function latestByQuestionId(array $question_ids): array
    {
        $question_revisions = DB::table('question_revisions')
            ->select()
            ->whereIn('question_id', $question_ids)
            ->orderBy('question_revisions.id')
            ->get();

        $question_revisions_by_question_id = [];
        foreach ($question_revisions as $question_revision) {
            $question_revisions_by_question_id[$question_revision->question_id] = $question_revision;
        }
        return $question_revisions_by_question_id;
    }
}
