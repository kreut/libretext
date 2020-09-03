<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDefaultPointsPerQuestionToAssignments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->unsignedTinyInteger('default_points_per_question')->after('due');
            $table->dropColumn(['num_submissions_needed', 'type_of_submission']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropColumn('default_points_per_question');
            $table->enum('num_submissions_needed', ['2', '3', '4', '5', '6', '7', '8', '9'])->after('due');
            $table->enum('type_of_submission', ['completed', 'correct'])->after('due');
        });
    }
}
