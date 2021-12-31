<?php

use App\Question;
use App\SavedQuestionsFolder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateFolderIdForCurrentQuestionsToQuestions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $question = new Question();
        $my_questions = $question->select('question_editor_user_id')
            ->whereNotNull('question_editor_user_id')
            ->groupBy('question_editor_user_id')
            ->pluck('question_editor_user_id');
        foreach ($my_questions as $question_editor_user_id) {
            $savedFolder = new SavedQuestionsFolder();
            $savedFolder->user_id = $question_editor_user_id;
            $savedFolder->type = 'my_questions';
            $savedFolder->name = 'Default';
            $savedFolder->save();
            DB::table('questions')
                ->where('question_editor_user_id', $question_editor_user_id)
                ->update(['folder_id' => $savedFolder->id]);
        }

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('questions', function (Blueprint $table) {
            DB::table('questions')->update(['folder_id' => null] );
            DB::table('saved_questions_folders')->where('type', 'my_questions')->delete();
        });
    }
}
