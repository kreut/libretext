<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateSuccessToLtiGradePassbacks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('lti_grade_passbacks', function (Blueprint $table) {
            $table->dropColumn('success');
            $table->string('status',10)->after('score');
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
            $table->boolean('success');
            $table->dropColumn('statuts');
        });
    }
}
