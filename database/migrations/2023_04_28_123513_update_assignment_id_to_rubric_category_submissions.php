<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateAssignmentIdToRubricCategorySubmissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rubric_category_submissions', function (Blueprint $table) {
            $table->unsignedBigInteger('assignment_id')->after('id');
        });
        Schema::table('rubric_category_submissions', function (Blueprint $table) {
            DB::table('rubric_category_submissions')->update(['assignment_id' => 19088]);
        });
        Schema::table('rubric_category_submissions', function (Blueprint $table) {
            $table->foreign('assignment_id')->references('id')->on('assignments');
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
            $table->dropColumn('assignment_id');
        });
    }
}
