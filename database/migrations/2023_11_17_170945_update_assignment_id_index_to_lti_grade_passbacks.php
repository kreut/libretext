<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateAssignmentIdIndexToLtiGradePassbacks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('lti_grade_passbacks', function (Blueprint $table) {
            $table->index('assignment_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('lti_grade_passbacks', function (Blueprint $table) {
            $table->dropIndex('lti_grade_passbacks_assignment_id_index');
        });
    }
}
