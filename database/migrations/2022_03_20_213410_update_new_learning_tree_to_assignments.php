<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateNewLearningTreeToAssignments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->string('learning_tree_success_level',6)
                ->after('hint_penalty')
                ->nullable();
            $table->string('learning_tree_success_criteria',20)
                ->after('learning_tree_success_level')
                ->nullable();
            $table->unsignedSmallInteger('min_number_of_successful_branches')
                ->after('learning_tree_success_criteria')
                ->nullable();
            $table->unsignedSmallInteger('min_time')
                ->after('min_number_of_successful_branches')
                ->nullable()
                ->comment('in minutes');
            $table->unsignedSmallInteger('min_number_of_successful_assessments')
                ->after('min_time')
                ->nullable();
            $table->boolean('reset_points')
                ->after('min_number_of_successful_assessments')
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
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropColumn(['learning_tree_success_level',
                'learning_tree_success_criteria',
                'min_number_of_successful_branches',
                'min_time',
                'min_number_of_successful_assessments',
                'reset_points']);
        });
    }
}
