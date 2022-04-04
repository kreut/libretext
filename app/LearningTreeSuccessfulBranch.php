<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LearningTreeSuccessfulBranch extends Model
{
    protected $table = 'learning_tree_successful_branches';
    protected $guarded = [];

    public function createIfNotExists(int $user_id, int $assignment_id, int $learning_tree_id, int $branch_id)
    {
        $successful_branch_exists = LearningTreeSuccessfulBranch::where('user_id', $user_id)
            ->where('assignment_id', $assignment_id)
            ->where('learning_tree_id', $learning_tree_id)
            ->where('branch_id', $branch_id)
            ->first();
        if (!$successful_branch_exists) {
            LearningTreeSuccessfulBranch::create(['user_id' => $user_id,
                'assignment_id' => $assignment_id,
                'learning_tree_id' => $learning_tree_id,
                'branch_id' => $branch_id
            ]);
        }
        return $successful_branch_exists;
    }


    /**
     * @param int $user_id
     * @param int $assignment_id
     * @param int $learning_tree_id
     * @return mixed
     */
    public function currentNumberOfSuccessfulBranchesNotYetReset(int $user_id,
                                                                 int $assignment_id,
                                                                 int $learning_tree_id) {
        return $this->where('user_id', $user_id)
            ->where('assignment_id', $assignment_id)
            ->where('learning_tree_id', $learning_tree_id)
            ->where('applied_to_reset', 0)
            ->count();

    }



}
