<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CanGiveUp extends Model
{
    protected $guarded = [];

    public function store($user_id, $assignment_id, $question_id)
    {
        $assignment = Assignment::find($assignment_id);
        if ($assignment->assessment_type === 'real time'
            && $assignment->scoring_type === 'p'
            && $assignment->solutions_availability === 'automatic') {
            $can_give_up_exists = DB::table('can_give_ups')
                ->where('user_id', $user_id)
                ->where('assignment_id', $assignment_id)
                ->where('question_id', $question_id)
                ->first();
            if (  !$can_give_up_exists ) {
                $this->create(['user_id' => $user_id,
                    'assignment_id' => $assignment_id,
                    'question_id' => $question_id,
                    'status' => 'can give up']);
            }
        }
    }
}
