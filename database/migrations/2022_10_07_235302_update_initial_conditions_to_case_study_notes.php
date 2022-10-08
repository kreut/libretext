<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateInitialConditionsToCaseStudyNotes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('case_study_notes', function (Blueprint $table) {
            $table->dropColumn('identifier');
            $table->text('additional_data')->after('notes')->nullable();
            $table->renameColumn('notes','initial_conditions');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('case_study_notes', function (Blueprint $table) {
            //
        });
    }
}
