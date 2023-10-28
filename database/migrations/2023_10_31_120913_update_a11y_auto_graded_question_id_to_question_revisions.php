<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateA11yAutoGradedQuestionIdToQuestionRevisions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('question_revisions', function (Blueprint $table) {
            $table->unsignedBigInteger('a11y_auto_graded_question_id')->after('h5p_owner_imported')->nullable();
        });
        Schema::table('question_revisions', function (Blueprint $table) {
            $table->dropColumn(['a11y_technology', 'a11y_technology_id']);
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('question_revisions', function (Blueprint $table) {
            //
        });
    }
}
