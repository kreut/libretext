<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class AssignmentSyncQuestion extends Model
{

    public function getQuestionCountByAssignmentIds(Collection $assignment_ids)
    {
        $questions_count_by_assignment_id = [];
        $questions_count = DB::table('assignment_question')
            ->whereIn('assignment_id', $assignment_ids)
            ->groupBy('assignment_id')
            ->select(DB::raw('count(*) as num_questions'), 'assignment_id')
            ->get();

        //reogranize by assignment id
        foreach ($questions_count as $key => $value) {
            $questions_count_by_assignment_id[$value->assignment_id] = $value->num_questions;
        }
        return $questions_count_by_assignment_id;
    }

    public function getFormattedClickerStatus($question_info){

        if (time() >= strtotime($question_info->clicker_start) && time() <= strtotime($question_info->clicker_end)){
            return 'view_and_submit';
        }
        if (time() > strtotime($question_info->clicker_end) ){
            return 'view_and_not_submit';
        }
        return 'neither_view_nor_submit';
    }
}
