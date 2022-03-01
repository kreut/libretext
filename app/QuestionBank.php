<?php

namespace App;

use App\Helpers\Helper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class QuestionBank extends Model
{
    public function getSupplementaryQuestionInfo($potential_questions, Assignment $userAssignment = null, Array $options = [])
    {

        $efs_dir = '/mnt/local/';
        $is_efs = is_dir($efs_dir);
        $storage_path = $is_efs
            ? $efs_dir
            : Storage::disk('local')->getAdapter()->getPathPrefix();
        $question_in_assignment_information = $userAssignment ? $userAssignment->questionInAssignmentInformation() : [];
        $my_favorites = DB::table('my_favorites')
            ->join('saved_questions_folders', 'my_favorites.folder_id', '=', 'saved_questions_folders.id')
            ->where('my_favorites.user_id', request()->user()->id)
            ->select('question_id', 'folder_id', 'name')
            ->get();
        $my_favorites_by_question_id = [];
        foreach ($my_favorites as $my_favorite) {
            $my_favorites_by_question_id[$my_favorite->question_id] = ['folder_id' => $my_favorite->folder_id,
                'name' => $my_favorite->name];

        }
        foreach ($potential_questions as $assignment_question) {
            $assignment_question->submission = Helper::getSubmissionType($assignment_question);
            $assignment_question->in_current_assignment = false;
            $assignment_question->in_other_assignments = false;
            $assignment_question->in_assignments_names = '';
            $assignment_question->in_assignments_count = 0;
            if (isset($question_in_assignment_information[$assignment_question->question_id])) {
                $assignment_question->in_assignments_count = count($question_in_assignment_information[$assignment_question->question_id]);
                if (in_array($userAssignment->name, $question_in_assignment_information[$assignment_question->question_id])) {
                    $assignment_question->in_current_assignment = true;
                }
                $assignment_question->in_other_assignments = ($assignment_question->in_current_assignment && $assignment_question->in_assignments_count > 1)
                    || (!$assignment_question->in_current_assignment && $assignment_question->in_assignments_count > 0);

                foreach ($question_in_assignment_information[$assignment_question->question_id] as $assignment_key => $assignment_name) {
                    if ($assignment_name === $userAssignment->name) {
                        unset($question_in_assignment_information[$assignment_question->question_id][$assignment_key]);
                        $assignment_question->in_assignments_count--;
                    }
                }
                $assignment_question->in_assignments_names = implode(', ', $question_in_assignment_information[$assignment_question->question_id]);

            }
            if (isset($options['text_question'])) {
                $non_technology_text_file = "$storage_path$assignment_question->library/$assignment_question->page_id.php";
                if (file_exists($non_technology_text_file)) {
                    //add this for searching
                    $assignment_question->text_question .= file_get_contents($non_technology_text_file);
                }
            }
            if (isset($my_favorites_by_question_id[$assignment_question->question_id])) {
                $assignment_question->my_favorites_folder_id = $my_favorites_by_question_id[$assignment_question->question_id]['folder_id'];
                $assignment_question->my_favorites_folder_name = $my_favorites_by_question_id[$assignment_question->question_id]['name'];
            }
            if (isset($options['tags'])) {
                $assignment_question->tags = isset($tags_by_question_id[$assignment_question->question_id]) ? implode(', ', $tags_by_question_id[$assignment_question->question_id]) : 'none';
            }}
        return $potential_questions;
    }
}
