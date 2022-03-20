<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateLearningTreeSuccessCriteriaSatisfiedToSubmissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->unsignedTinyInteger('learning_tree_success_criteria_satisfied')
                ->after('explored_learning_tree')
                ->default(0);
            $table->dropColumn('explored_learning_tree');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropColumn('learning_tree_success_criteria_satisfied');
            $table->unsignedTinyInteger('explored_learning_tree')
                ->after('learning_tree_success_criteria_satisfied')
                ->nullable();
        });
    }
}
