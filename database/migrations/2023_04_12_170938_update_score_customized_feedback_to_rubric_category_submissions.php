<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateScoreCustomizedFeedbackToRubricCategorySubmissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rubric_category_submissions', function (Blueprint $table) {
            $table->renameColumn('customized_feedback','custom_feeback');
            $table->renameColumn('score','custom_score');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rubric_category_submissions', function (Blueprint $table) {
            //
        });
    }
}
