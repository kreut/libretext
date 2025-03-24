<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLtiNamesAndRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lti_names_and_roles', function (Blueprint $table) {
            $table->id();
            $table->string('lms_user_id');
            $table->unsignedBigInteger('course_id');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('status');
            $table->unique(['lms_user_id', 'course_id']);
            $table->dateTime('emailed_about_account')->nullable();
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
        Schema::dropIfExists('lti_names_and_roles');
    }
}
