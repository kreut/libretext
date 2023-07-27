<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateLearningTreeBranchSubmissionInformationToSubmissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::table('submissions', function (Blueprint $table) {
            $table->unsignedSmallInteger('number_branches_completed')
                ->after('reset_count')
                ->default(0);
        });
        Schema::table('submissions', function (Blueprint $table) {
            $table->renameColumn('reset_count', 'number_resets_available');
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
            $table->dropColumn('number_branches_completed');
        });
        Schema::table('submissions', function (Blueprint $table) {
            $table->renameColumn('number_resets_available', 'reset_count');
        });
    }
}
