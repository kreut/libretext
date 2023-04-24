<?php

use App\RubricCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateQuestionIdToRubricCategories extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rubric_categories', function (Blueprint $table) {
            $table->dropForeign('rubric_categories_assignment_id_foreign');
            $table->dropColumn('assignment_id');
        });
        Schema::table('rubric_categories', function (Blueprint $table) {
            $table->unsignedBigInteger('question_id')->after('id');

        });

        Schema::table('rubric_categories', function (Blueprint $table) {
            DB::table('rubric_categories')->update(['question_id' => 163524]);
            DB::table('questions')
                ->where('id', 163524)
                ->update(['question_type' => 'report']);
        });
        Schema::table('rubric_categories', function (Blueprint $table) {
            $table->foreign('question_id')
                ->references('id')
                ->on('questions');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public
    function down()
    {
        Schema::table('rubric_categories', function (Blueprint $table) {
            //
        });
    }
}
