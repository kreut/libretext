<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropQuestionIdToLearningTrees extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('learning_trees', function (Blueprint $table) {
            $table->dropForeign('learning_trees_user_id_foreign');
            $table->dropIndex('learning_trees_user_id_question_id_unique');
            $table->dropColumn('question_id');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('learning_trees', function (Blueprint $table) {
            //
        });
    }
}
