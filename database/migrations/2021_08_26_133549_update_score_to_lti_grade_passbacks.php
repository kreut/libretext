<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateScoreToLtiGradePassbacks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('lti_grade_passbacks', function (Blueprint $table) {
            $table->string('score')->after('launch_id');
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
            $table->dropColumn('score');
        });
    }
}
