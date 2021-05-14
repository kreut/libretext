<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGraderNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('grader_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->boolean('when_assignments_are_closed')->nullable();
            $table->boolean('for_late_submissions')->nullable();
            $table->string('grading_reminder_time_period')->nullable();
            $table->boolean('copy_grading_reminder_to_instructor')->nullable();
            $table->boolean('copy_grading_reminder_to_head_grader')->nullable();
            $table->timestamps();

            $table->foreign('course_id')->references('id')->on('courses');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('grader_notifications');
    }
}
