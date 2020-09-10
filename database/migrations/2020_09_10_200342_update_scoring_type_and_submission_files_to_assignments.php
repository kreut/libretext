<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateScoringTypeAndSubmissionFilesToAssignments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->string('scoring_type',1)
                    ->comment("p=points, c=completed")->change();
            $table->string('submission_files',1)
                ->comment("q=per question, c=per assignment, 0=none")->change();

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
            //
        });
    }
}
