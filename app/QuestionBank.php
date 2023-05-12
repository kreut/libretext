<?php

namespace App;

use App\Helpers\Helper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class QuestionBank extends Model
{
    public function getSupplementaryQuestionInfo($potential_questions,
                                                 Assignment $userAssignment = null,
                                                 array $options = [],
                                                 array $tags_by_question_id = [])
    {

        $question_ids = $potential_questions->pluck('question_id')->toArray();
        $efs_dir = '/mnt/local/';
        $is_efs = is_dir($efs_dir);
        $storage_path = $is_efs
            ? $efs_dir
            : Storage::disk('local')->getAdapter()->getPathPrefix();
        $question_in_assignment_information = $userAssignment ? $userAssignment->questionInAssignmentInformation() : [];
        if ($question_ids) {
            $latest_revisions = DB::table('question_revisions as qr1')
                ->select('qr1.id', 'qr1.question_id')
                ->join(DB::raw('(SELECT question_id, MAX(revision_number) AS max_revision_number FROM question_revisions WHERE question_id IN (' . implode(',', $question_ids) . ') GROUP BY question_id) qr2'), function ($join) {
                    $join->on('qr1.question_id', '=', 'qr2.question_id');
                    $join->on('qr1.revision_number', '=', 'qr2.max_revision_number');
                })
                ->get();
            $latest_revisions_by_question_id = [];
            foreach ($latest_revisions as $latest_revision) {
                $latest_revisions_by_question_id[$latest_revision->question_id] = $latest_revision->id;
            }
        }


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
        $formative_question_ids = [];
        $formative_questions = DB::table('assignment_question')
            ->join('assignments', 'assignment_question.assignment_id', '=', 'assignments.id')
            ->join('courses', 'assignments.course_id', '=', 'courses.id')
            ->where('courses.formative', 1)
            ->select('question_id')
            ->get();
        if ($formative_questions) {
            $formative_question_ids = $formative_questions->pluck('question_id')->toArray();
        }

        foreach ($potential_questions as $assignment_question) {
            $assignment_question->submission = Helper::getSubmissionType($assignment_question);
            $assignment_question->in_current_assignment = false;
            $assignment_question->in_other_assignments = false;
            $assignment_question->in_formative_assignment = in_array($assignment_question->question_id, $formative_question_ids);
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
            if (in_array('text_question', $options)) {
                $non_technology_text_file = "$storage_path$assignment_question->library/$assignment_question->page_id.php";
                if (file_exists($non_technology_text_file)) {
                    //add this for searching will do when in the database
                    // $assignment_question->text_question .= file_get_contents($non_technology_text_file);
                }
            }
            if (isset($my_favorites_by_question_id[$assignment_question->question_id])) {
                $assignment_question->my_favorites_folder_id = $my_favorites_by_question_id[$assignment_question->question_id]['folder_id'];
                $assignment_question->my_favorites_folder_name = $my_favorites_by_question_id[$assignment_question->question_id]['name'];
            }
            if (in_array('tags', $options)) {
                $assignment_question->tags = $tags_by_question_id[$assignment_question->question_id] ?? [];
            }
            $assignment_question->latest_revision_id = $latest_revisions_by_question_id[$assignment_question->question_id] ?? null;

        }

        return $potential_questions;
    }
}
