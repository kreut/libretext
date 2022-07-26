<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


class SavedQuestionsFolder extends Model
{
    /**
     * @param int $user_id
     * @param array $question_ids
     * @return void
     */
    function moveQuestionsToNewOwnerInTransferredQuestions(int $user_id, Array $question_ids)
    {
        $saved_questions_folder = DB::table('saved_questions_folders')
            ->where('user_id', $user_id)
            ->where('type', 'my_questions')
            ->where('name', 'Transferred Questions')
            ->first();
        if (!$saved_questions_folder) {
            $savedQuestionsFolder = new SavedQuestionsFolder();
            $savedQuestionsFolder->user_id =  $user_id;
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
