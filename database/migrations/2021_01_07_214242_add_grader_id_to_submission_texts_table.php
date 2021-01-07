<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGraderIdToSubmissionTextsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('submission_texts', function (Blueprint $table) {
            $table->unsignedBigInteger('grader_id')->after('date_graded')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('submission_texts', function (Blueprint $table) {
            //
        });
    }
}
