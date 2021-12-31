<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateFolderAsForeignKeyToSavedQuestions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('saved_questions', function (Blueprint $table) {
            $table->foreign('folder_id')
                ->references('id')
                ->on('saved_questions_folders');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('saved_questions', function (Blueprint $table) {
            $table->dropForeign('saved_questions_folder_id_foreign');
        });
    }
}
