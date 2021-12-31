<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SavedQuestionsFolder extends Model
{
    public function moveThenDeleteFolder($request, MyFavorite $myFavorite, Question $question){

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
