<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class AssignmentSyncQuestion extends Model
{

    public function addLearningTreeIfBetaAssignment(int $assignment_question_id,
                                                    int $assignment_id,
                                                    int $question_id)
    {
        $beta_learning_tree = DB::table('beta_course_approvals')
            ->where('beta_assignment_id', $assignment_id)
            ->where('beta_question_id', $question_id)
            ->where('beta_learning_tree_id','<>',0)
            ->first();
        if ($beta_learning_tree) {
            DB::table('assignment_question_learning_tree')
                ->insert([
                    'assignment_question_id' => $assignment_question_id,
                    'learning_tree_id' => $beta_learning_tree->id,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);
        }

    }

    public function completedAllAssignmentQuestions($assignment)
    {
        $num_technology_questions = $assignment->number_of_randomized_assignments
            ?: DB::table('assignment_question')
                ->where('assignment_id', $assignment->id)
                ->join('questions', 'assignment_question.question_id', '=', 'questions.id')
                ->where('technology', '<>', 'text')
                ->count();
        $num_non_technology_questions = DB::table('assignment_question')
            ->where('assignment_id', $assignment->id)
            ->where('open_ended_submission_type', '<>', '0')
            ->get()
            ->count();
        if ($num_technology_questions + $num_non_technology_questions === 0) {
            return false;
        }
        $num_submitted_technology_questions = DB::table('submissions')
            ->where('assignment_id', $assignment->id)
            ->where('user_id', Auth::user()->id)
            ->get()
            ->count();
        if ($num_technology_questions !== $num_submitted_technology_questions) {
            return false;
        }
        $num_submitted_non_technology_questions = DB::table('submission_files')
            ->where('assignment_id', $assignment->id)
            ->where('user_id', Auth::user()->id)
            ->where('type', '<>', 'a')
            ->get()
            ->count();
        return $num_submitted_non_technology_questions === $num_non_technology_questions;

    }

    public function importAssignmentQuestionsAndLearningTrees(int $from_assignment_id, int $to_assignment_id)
    {
        $assignment_questions = DB::table('assignment_question')
            ->where('assignment_id', $from_assignment_id)
            ->get();
        foreach ($assignment_questions as $key => $assignment_question) {
            $assignment_question->assignment_id = $to_assignment_id;
            //add each question
            $assignment_question_array = json_decode(json_encode($assignment_question), true);
            unset($assignment_question_array['id']);
            $new_assignment_question_id = DB::table('assignment_question')->insertGetId($assignment_question_array);
            //add the learning tree associated with the question
            $assignment_question_learning_tree = DB::table('assignment_question_learning_tree')
                ->where('assignment_question_id', $assignment_question->id)
                ->first();
            if ($assignment_question_learning_tree) {
                DB::table('assignment_question_learning_tree')->insert([
                    'assignment_question_id' => $new_assignment_question_id,
                    'learning_tree_id' => $assignment_question_learning_tree->learning_tree_id,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()]);
            }
        }
    }

    public function getNewQuestionOrder(Assignment $assignment)
    {
        $max_order = DB::table('assignment_question')
            ->where('assignment_id', $assignment->id)
            ->max('order');
        return $max_order ? $max_order + 1 : 1;
    }

    public function getQuestionCountByAssignmentIds(Collection $assignments)
    {
        $questions_count_by_assignment_id = [];
        foreach ($assignments as $assignment) {
            if ($assignment->number_of_randomized_assessments) {
                $questions_count_by_assignment_id[$assignment->id] = $assignment->number_of_randomized_assessments;
            } else {
                $non_randomized_assignment_ids[] = $assignment->id;
            }
        }

        $questions_count = DB::table('assignment_question')
            ->whereIn('assignment_id', $non_randomized_assignment_ids)
            ->groupBy('assignment_id')
            ->select(DB::raw('count(*) as num_questions'), 'assignment_id')
            ->get();

        //reogranize by assignment id
        foreach ($questions_count as $key => $value) {
            $questions_count_by_assignment_id[$value->assignment_id] = $value->num_questions;
        }
        return $questions_count_by_assignment_id;
    }

    public function getFormattedClickerStatus($question_info)
    {

        if (time() >= strtotime($question_info->clicker_start) && time() <= strtotime($question_info->clicker_end)) {
            return 'view_and_submit';
        }
        if (time() > strtotime($question_info->clicker_end)) {
            return 'view_and_not_submit';
        }
        return 'neither_view_nor_submit';
    }

    public function orderQuestions(array $ordered_questions, Assignment $assignment)
    {
        foreach ($ordered_questions as $key => $question_id) {
            DB::table('assignment_question')->where('assignment_id', $assignment->id)
                ->where('question_id', $question_id)
                ->update(['order' => $key + 1]);
        }

    }
}
