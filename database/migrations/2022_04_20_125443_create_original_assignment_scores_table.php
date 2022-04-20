<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOriginalAssignmentScoresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('original_assignment_scores', function (Blueprint $table) {
            $table->unsignedBigInteger('score_id')->unique();
            $table->unsignedBigInteger('user_id');
            $table->string('email');
            $table->unsignedBigInteger('assignment_id');
            $table->string('assignment_name');
            $table->string('original_score')->nullable();
            $table->string('new_score')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('original_assignment_scores');
    }
}
