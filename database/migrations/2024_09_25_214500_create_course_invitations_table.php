<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCourseInvitationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('course_invitations', function (Blueprint $table) {
            $table->id();
            $table->string('access_code');
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('section_id');
            $table->string('last_name');
            $table->string('first_name');
            $table->string('email');
            $table->string('student_id')->nullable();
            $table->string('status');
            $table->string('message',3000)->nullable();
            $table->timestamps();
            $table->foreign('course_id')->references('id')->on('courses');
            $table->foreign('section_id')->references('id')->on('sections');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('course_invitations');
    }
}
