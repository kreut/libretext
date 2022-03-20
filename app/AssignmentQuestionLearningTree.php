<?php

namespace App;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AssignmentQuestionLearningTree extends Model
{
    public function getAssignmentQuestionLearningTreeByRootNodeQuestionId(int $assignment_id, int $root_node_question_id)
    {
        return DB::table('assignment_question_learning_tree')
            ->join('assignment_question', 'assignment_question_learning_tree.assignment_question_id', '=', 'assignment_question.id')
            ->select('assignment_question_learning_tree.*')
            ->where('assignment_question.assignment_id', $assignment_id)
            ->where('assignment_question.question_id', $root_node_question_id)
            ->first();
    }

    /**
     * @throws Exception
     */
    public function getAssignmentQuestionLearningTreeByLearningTreeId(int $assignment_id, int $learning_tree_id)
    {
        $assignment_question_learning_tree = DB::table('assignment_question')
            ->join('assignment_question_learning_tree', 'assignment_question.id', '=', 'assignment_question_learning_tree.assignment_question_id')
            ->select('assignment_question_learning_tree.*')
            ->where('assignment_question.assignment_id', $assignment_id)
            ->where('assignment_question_learning_tree.learning_tree_id', $learning_tree_id)
            ->first();
        if (!$assignment_question_learning_tree) {
            throw new Exception ("Assignment question with assignment id $assignment_id and learning tree id $learning_tree_id does not exist.");
        }
        return $assignment_question_learning_tree;
    }
}
