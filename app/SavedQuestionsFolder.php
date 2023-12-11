<?php

namespace App;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


class SavedQuestionsFolder extends Model
{

    /**
     * @param User $user
     * @param string $type
     * @param int $withH5P
     * @return array
     * @throws Exception
     */
    public function getSavedQuestionsFoldersByType(User $user, string $type,  int $withH5P = 0): array
    {
        $question = new Question();
        switch ($type) {
            case('my_favorites'):
                $questions_table = 'my_favorites';
                break;
            case('my_questions'):
                if ($withH5P) {
                    $h5p_responses = $question->autoImportH5PQuestions($user);
                    foreach ($h5p_responses as $key => $value) {
                        if ($key !== 'type') {
                            $response[$key] = $value;
                        }
                        if ($key === 'type' && $value === 'error' && isset($h5p_responses['message'])) {
                            $response['message'] = $h5p_responses['message'];
                            return $response;
                        }
                    }
                }
                $questions_table = 'questions';
                $empty_learning_tree_nodes = DB::table('empty_learning_tree_nodes')
                    ->join('questions', 'empty_learning_tree_nodes.question_id', '=', 'questions.id')
                    ->select('folder_id', DB::raw("COUNT(questions.id) as num_questions"))
                    ->groupBy('folder_id')
                    ->get();
                $empty_learning_tree_nodes_by_folder_id = [];
                foreach ($empty_learning_tree_nodes as $empty_learning_tree_node) {
                    $empty_learning_tree_nodes_by_folder_id[$empty_learning_tree_node->folder_id] = $empty_learning_tree_node->num_questions;
                }
                break;
            default:
                $questions_table = '';
        }
        $saved_questions_folders = DB::table('saved_questions_folders')
            ->leftJoin($questions_table, 'saved_questions_folders.id', '=', "$questions_table.folder_id")
            ->where('saved_questions_folders.user_id', $user->id)
            ->where('saved_questions_folders.type', $type);

        $saved_questions_folders = $saved_questions_folders
            ->select('saved_questions_folders.id', 'name', DB::raw("COUNT($questions_table.id) as num_questions"))
            ->groupBy('id')
            ->get();

        if ($type === 'my_questions') {
            foreach ($saved_questions_folders as $key => $saved_questions_folder) {
                if (isset($empty_learning_tree_nodes_by_folder_id[$saved_questions_folder->id])) {
                    $saved_questions_folders[$key]->num_questions = $saved_questions_folders[$key]->num_questions - $empty_learning_tree_nodes_by_folder_id[$saved_questions_folder->id];
                }
            }
        }

        if ($saved_questions_folders->isEmpty()) {
            $savedQuestionsFolder = new SavedQuestionsFolder();
            $savedQuestionsFolder->user_id = $user->id;
            $savedQuestionsFolder->name = 'Main';
            $savedQuestionsFolder->type = $type;
            $savedQuestionsFolder->save();
            $saved_questions_folders = [$savedQuestionsFolder];
        }
        if ($type === 'my_questions') {
            $saved_questions_folders = $this->getMyQuestionsFoldersWithH5pImportsAndTransferredQuestionsFirst($saved_questions_folders);

        }
        $response['saved_questions_folders'] = $saved_questions_folders;
        $response['type'] = 'success';
        return $response;
    }

    /**
     * @param int $user_id
     * @param array $question_ids
     * @return void
     */
    function moveQuestionsToNewOwnerInTransferredQuestions(int $user_id, array $question_ids)
    {
        $saved_questions_folder = DB::table('saved_questions_folders')
            ->where('user_id', $user_id)
            ->where('type', 'my_questions')
            ->where('name', 'Transferred Questions')
            ->first();
        if (!$saved_questions_folder) {
            $savedQuestionsFolder = new SavedQuestionsFolder();
            $savedQuestionsFolder->user_id = $user_id;
            $savedQuestionsFolder->name = 'Transferred Questions';
            $savedQuestionsFolder->type = 'my_questions';
            $savedQuestionsFolder->save();
            $folder_id = $savedQuestionsFolder->id;
        } else {
            $folder_id = $saved_questions_folder->id;
        }
        Question::whereIn('id', $question_ids)
            ->update(['question_editor_user_id' => $user_id,
                'folder_id' => $folder_id,
                'updated_at' => now()]);
    }

    /**
     * @param $saved_questions_folders
     * @return array
     */
    public function getMyQuestionsFoldersWithH5pImportsAndTransferredQuestionsFirst($saved_questions_folders): array
    {
        $saved_questions_folders_with_h5p_first = [];
        foreach ($saved_questions_folders as $key => $saved_questions_folder) {
            if ($saved_questions_folder->name === 'Transferred Questions') {
                $saved_questions_folders_with_h5p_first[0] = $saved_questions_folder;
                unset($saved_questions_folders[$key]);
            }
            if ($saved_questions_folder->name === 'H5P Imports') {
                $saved_questions_folders_with_h5p_first[1] = $saved_questions_folder;
                unset($saved_questions_folders[$key]);
            }
        }

        foreach ($saved_questions_folders as $saved_questions_folder) {
            $saved_questions_folders_with_h5p_first[] = $saved_questions_folder;
        }

        $saved_questions_folders = [];
        foreach ($saved_questions_folders_with_h5p_first as $folder) {
            $saved_questions_folders[] = (array)$folder;
        }
        return $saved_questions_folders;
    }

    /**
     * @param int $folder_id
     * @return mixed
     */
    public function isOwner(int $folder_id)
    {
        return $this->where('user_id', Auth::user()->id)
            ->where('id', $folder_id)
            ->first();

    }

    public function moveThenDeleteFolder($request, MyFavorite $myFavorite, Question $question)
    {

        switch ($request->question_source) {
            case('my_favorites'):
                $current_folder_questions = $myFavorite->where('folder_id', $this->id)->get();
                foreach ($current_folder_questions as $current_folder_question) {
                    if (!$myFavorite
                        ->where('folder_id', $request->move_to_folder_id)
                        ->where('user_id', $request->user_id)
                        ->where('question_id', $current_folder_question->question_id)
                        ->exists()) {
                        $current_folder_question->folder_id = $request->move_to_folder_id;
                        $current_folder_question->save();
                    }
                }
                break;
            case('my_questions'):
                $current_folder_questions = $question->where('folder_id', $this->id)->get();
                foreach ($current_folder_questions as $current_folder_question) {
                    if (!$question
                        ->where('folder_id', $request->move_to_folder_id)
                        ->where('question_editor_user_id', $request->user_id)
                        ->where('id', $current_folder_question->question_id)
                        ->exists()) {
                        $current_folder_question->folder_id = $request->move_to_folder_id;
                        $current_folder_question->save();
                    }
                }
                break;
            default:
                $response['message'] = "$request->type is not a valid question source.";
                return $response;
        }
    }
}
