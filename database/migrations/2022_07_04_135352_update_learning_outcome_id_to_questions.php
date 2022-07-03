<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateLearningOutcomeIdToQuestions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('questions', function (Blueprint $table) {
            //$table->unsignedBigInteger('learning_outcome_id')->nullable();
            $table->foreignId('learning_outcome_id')->nullable()->constrained()->change();
        });
        DB::statement("ALTER TABLE questions MODIFY COLUMN learning_outcome_id BIGINT UNSIGNED AFTER title");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropForeign('questions_learning_outcome_id_foreign');
            $table->dropColumn('learning_outcome_id');
        });
    }
}
