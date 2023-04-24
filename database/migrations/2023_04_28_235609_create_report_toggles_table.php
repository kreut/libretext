<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReportTogglesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('report_toggles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assignment_id');
            $table->unsignedBigInteger('question_id');
            $table->unsignedTinyInteger('points')->default(0);
            $table->unsignedTinyInteger('comments')->default(0);
            $table->unsignedTinyInteger('criteria')->default(0);
            $table->timestamps();
            $table->foreign('assignment_id')->references('id')->on('assignments');
            $table->foreign('question_id')->references('id')->on('questions');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('report_toggles');
    }
}
