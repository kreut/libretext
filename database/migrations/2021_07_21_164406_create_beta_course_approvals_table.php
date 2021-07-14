<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBetaCourseApprovalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('beta_course_approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('beta_assignment_id');
            $table->unsignedBigInteger('beta_question_id');
            $table->string('action',6);
            $table->timestamps();
            $table->foreign('beta_assignment_id')->references('id')->on('assignments');
            $table->foreign('beta_question_id')->references('id')->on('questions'); });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('beta_course_approvals');
    }
}
