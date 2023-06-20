<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateQuestionRevisionIdToRubricCategories extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rubric_categories', function (Blueprint $table) {
            $table->unsignedBigInteger('question_revision_id')->after('question_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rubric_categories', function (Blueprint $table) {
            $table->dropColumn('question_revision_id');
        });
    }
}
