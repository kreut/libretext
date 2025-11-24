<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubmissionsRecomputedScores extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('submissions_recomputed_scores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('submission_id');
            $table->decimal('score', 8, 4);
            $table->decimal('original_score', 8, 4);
            $table->timestamps();
            $table->foreign('submission_id')->references('id')->on('submissions');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('submissions_recomputed_scores');
    }
}
