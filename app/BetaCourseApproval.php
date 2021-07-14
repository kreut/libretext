<?php

namespace App;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BetaCourseApproval extends Model
{
    protected $guarded = [];

    public function updateBetaCourseApprovalsForQuestion(Assignment $assignment,
                                                         int $question_id,
                                                         string $action,
                                                         int $learning_tree_id = 0)
    {
        if (!in_array($action, ['add', 'remove'])) {
            throw new Exception ('That is not a valid action.');
        }
        if ($assignment->course->alpha) {
            $beta_assignments = $assignment->betaAssignments();
            // if this is an alpha course add the approvals
            foreach ($beta_assignments as $beta_assignment) {
                $course_approval_exists = BetaCourseApproval::where('beta_assignment_id', $beta_assignment->id)
                    ->where('beta_question_id', $question_id)
                    ->where('beta_learning_tree_id', $learning_tree_id)
                    ->first();
                if ($course_approval_exists) {
                    //add becomes remove or remove becomes an add; either way the beta course doesn't change
                    BetaCourseApproval::where('beta_assignment_id', $beta_assignment->id)
                        ->where('beta_question_id', $question_id)
                        ->where('beta_learning_tree_id', $learning_tree_id)
                        ->delete();
                } else {
                    BetaCourseApproval::create([
                        'beta_assignment_id' => $beta_assignment->id,
                        'action' => $action,
                        'beta_question_id' => $question_id,
                        'beta_learning_tree_id' => $learning_tree_id]);
                }
            }
        } else {
            ///if this is a Beta assignment, remove any approvals
            BetaCourseApproval::where('beta_question_id', $question_id)
                ->where('beta_assignment_id', $assignment->id)
                ->delete();
        }
    }

}
