<?php

use App\SavedQuestion;
use App\SavedQuestionsFolder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateFolderIdToSavedQuestions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('saved_questions', function (Blueprint $table) {
            $table->unsignedBigInteger('folder_id')->after('user_id');
        });
        $savedQuestion = new SavedQuestion();
        $saved_questions = $savedQuestion->select('user_id')->groupBy('user_id')->pluck('user_id');
        foreach ($saved_questions as $user_id){
            $savedFolder = new SavedQuestionsFolder();
            $savedFolder->user_id = $user_id;
            $savedFolder->name  ='Default';
            $savedFolder->save();
            DB::table('saved_questions')
                ->where('user_id', $user_id)
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
        Schema::table('saved_questions', function (Blueprint $table) {
            $table->dropColumn('folder_id');
        });
    }
}
