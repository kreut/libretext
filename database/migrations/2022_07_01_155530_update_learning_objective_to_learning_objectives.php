<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateLearningObjectiveToLearningObjectives extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('learning_objectives', function (Blueprint $table) {
            $table->renameColumn('learning_objective','description');
            $table->rename('learning_outcomes');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('learning_objectives', function (Blueprint $table) {
            //
        });
    }
}
