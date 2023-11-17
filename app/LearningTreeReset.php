<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class LearningTreeReset extends Model
{
    protected $guarded = [];

    /**
     * @param Assignment $assignment
     * @param Question $question
     * @param User $user
     * @return bool
     */
    public function canResetRootNodeSubmission(Assignment $assignment, Question $question, User $user): bool
    {
        $questionLevelOverride = new QuestionLevelOverride();
        $assignmentLevelOverride = new AssignmentLevelOverride();
        $has_question_level_override = $questionLevelOverride->hasAutoGradedOverride($assignment->id, $question->id, $assignmentLevelOverride);
       if ($has_question_level_override || $user->fake_student){
           return true;
       }
       $assign_to_timing = $assignment->assignToTimingByUser();
        $extension = DB::table('extensions')
            ->select('extension')
            ->where('assignment_id', $assignment->id)
            ->where('user_id', $user->id)
            ->first('extension');
        $past_due = time() > strtotime($assignment->assignToTimingByUser('due'));
        if ($past_due) {
            if ($extension) {
                return strtotime($extension->extension) < time();
            }

            if ($assignment->late_policy === 'not accepted') {
                return false;
            }
            if (in_array($assignment->late_policy, ['deduction', 'marked late'])) {
                //now let's check the late policy deadline
                //if past policy deadline
                if (strtotime($assign_to_timing->final_submission_deadline) < time()) {
                    return false;
                }
            }
        }
        return true;
    }
}
