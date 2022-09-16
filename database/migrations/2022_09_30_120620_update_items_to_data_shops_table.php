<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateItemsToDataShopsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('data_shops', function (Blueprint $table) {
            $table->renameColumn('level','assignment_id');
            $table->renameColumn('level_name','assignment_name');
            $table->renameColumn('level_group','assignment_group');
            $table->renameColumn('level_scoring_type','assignment_scoring_type');
            $table->renameColumn('level_points','assignment_points');
            $table->renameColumn('problem_name','question_id');
            $table->renameColumn('problem_points','question_points');
            $table->renameColumn('problem_view','question_view');
            $table->renameColumn('class','course_id');
            $table->renameColumn('class_name','course_name');
            $table->renameColumn('class_start_date','course_start_date');
            $table->dropColumn('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('data_shops', function (Blueprint $table) {
            //
        });
    }
}
