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
            $table->unsignedBigInteger('alpha_assignment_question_id')->nullable();
            $table->unsignedBigInteger('beta_question_id')->nullable();
            $table->timestamps();
            $table->foreign('beta_assignment_id')->references('id')->on('assignments');
        });
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
