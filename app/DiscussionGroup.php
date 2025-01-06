<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DiscussionGroup extends Model
{
    protected $guarded = [];

    /**
     * @param int $assignment_id
     * @param int $question_id
     * @param int $user_id
     * @return mixed
     */
    public function store(int $assignment_id, int $question_id, int $user_id)
    {
        $discussion_group = DB::table('discussion_groups')
            ->where('assignment_id', $assignment_id)
            ->where('question_id', $question_id)
            ->where('user_id', $user_id)
            ->first();
        if (!$discussion_group) {
            $group = DiscussionGroup::select('group')
                ->where('assignment_id', $assignment_id)
                ->where('question_id', $question_id)
                ->groupBy('group')
                ->orderByRaw('COUNT(*) ASC')
                ->limit(1)
                ->value('group');
            if (!$group) {
                $group = 1;
            }
            $discussion_group = new DiscussionGroup();
            $discussion_group->assignment_id = $assignment_id;
            $discussion_group->question_id = $question_id;
            $discussion_group->user_id = $user_id;
            $discussion_group->group = $group;
            $discussion_group->save();
        } else {
            $group = $discussion_group->group;
        }
        return $group;
    }

}
