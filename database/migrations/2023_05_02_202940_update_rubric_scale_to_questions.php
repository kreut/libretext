<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateRubricScaleToQuestions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->string('rubric_scale',15)->after('grading_style_id')->nullable();
        });

        Schema::table('questions', function (Blueprint $table) {
            DB::table('questions')->where('question_type','report')
                ->update(['rubric_scale'=>'percentage']);
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('rubric_scale');
        });
    }
}
