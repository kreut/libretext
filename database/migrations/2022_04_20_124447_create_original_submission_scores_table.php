<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateOriginalSubmissionScoresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('original_submission_scores', function (Blueprint $table) {
            $table->unsignedBigInteger('submission_id')->unique();
            $table->unsignedBigInteger('assignment_id');
            $table->string('email');
            $table->unsignedBigInteger('user_id');
            $table->decimal('original_score', 8, 4);
            $table->decimal('new_score', 8, 4)->nullable();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('original_submission_scores');
    }
}
