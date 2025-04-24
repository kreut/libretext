<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateUseExistingRubricToAssignmentQuestion extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('assignment_question', function (Blueprint $table) {
            $table->unsignedTinyInteger('use_existing_rubric')
                ->after('custom_rubric')
                ->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('assignment_question', function (Blueprint $table) {
            $table->dropColumn('use_existing_rubric');
        });
    }
}
