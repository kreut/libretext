<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ReviewHistory extends Model
{
    protected $guarded = [];

    public function store($user_id, $assignment_id, $question_id)
    {
        $this->user_id = $user_id;
        $this->assignment_id = $assignment_id;
        $this->question_id = $question_id;
        $this->save();
        session()->put('review_history', $this->id);
    }
}
