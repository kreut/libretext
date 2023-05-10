<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateAppliedLatePenaltyToSubmissionFiles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('submission_files', function (Blueprint $table) {
            $table->unsignedSmallInteger('applied_late_penalty')->after('score')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('submission_files', function (Blueprint $table) {
            $table->dropColumn('applied_late_penalty');
        });
    }
}
