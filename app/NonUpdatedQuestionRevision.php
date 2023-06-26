<?php

namespace App;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class NonUpdatedQuestionRevision extends Model
{

    /**
     * @param Course $course
     * @param QuestionRevision $questionRevision
     * @return array
     */
    public function nonUpdatedAssignmentQuestionsByCourse(Course $course, QuestionRevision $questionRevision): array
    {
        $assignment_ids = $course->assignments()->pluck('id')->toArray();
        $assignment_questions = DB::table('assignment_question')
            ->join('assignments', 'assignment_question.assignment_id', '=', 'assignments.id')
            ->join('questions', 'assignment_question.question_id', '=', 'questions.id')
            ->join('question_revisions', 'assignment_question.question_id', '=', 'question_revisions.question_id')
            ->where('question_revisions.id', '<>', 'assignment_question.question_revision_id')
            ->whereIn('assignment_id', $assignment_ids)
            ->select('assignment_question.id AS assignment_question_id',
                'assignment_question.custom_question_title',
                'assignment_question.order',
                'assignment_question.question_revision_id AS current_question_revision_id',
                'assignment_question.id AS assignment_question_id',
                'assignments.name AS assignment_name',
                'assignments.id AS assignment_id',
                'question_revisions.revision_number AS current_revision_number',
                'questions.id AS question_id',
                'questions.title')
            ->get();
        $question_ids = $assignment_questions->pluck('question_id')->toArray();

        $latest_question_revisions_by_question_id = $questionRevision->latestByQuestionId($question_ids);
        $non_updated_assignment_questions = [];
        $used_assignment_question_ids = [];
        foreach ($assignment_questions as $assignment_question) {
            if (!in_array($assignment_question->assignment_question_id, $used_assignment_question_ids)) {
                if ($assignment_question->current_question_revision_id !== $latest_question_revisions_by_question_id[$assignment_question->question_id]->id) {
                    $assignment_question->latest_question_revision_id = $latest_question_revisions_by_question_id[$assignment_question->question_id]->id;
                    $assignment_question->latest_revision_number = $latest_question_revisions_by_question_id[$assignment_question->question_id]->revision_number;
                    $non_updated_assignment_questions[] = $assignment_question;
                }
            }
            $used_assignment_question_ids[] = $assignment_question->assignment_question_id;
        }
        return $non_updated_assignment_questions;
    }
}
