<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateHintsAndHintsPenaltyToAssignments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->boolean('can_view_hint')->after('number_of_allowed_attempts_penalty')->nullable()->comment('applies to non-delayed assessments');
            $table->unsignedSmallInteger('hint_penalty')->after('can_view_hint')->nullable()->comment('applies to non-delayed assessments');
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
            $table->boolean('free_pass_for_satisfying_learning_tree_criteria')
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
            $table->dropColumn(['can_view_hint', 'hint_penalty']);
            $table->dropColumn(['learning_tree_success_level',
                'learning_tree_success_criteria',
                'min_number_of_successful_branches',
                'min_time',
                'min_number_of_successful_assessments',
                'free_pass_for_satisfying_learning_tree_criteria']);
        });

    }
}
