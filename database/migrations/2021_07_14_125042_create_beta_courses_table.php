<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBetaCoursesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('beta_courses', function (Blueprint $table) {
            $table->unsignedBigInteger('id');
            $table->unsignedBigInteger('alpha_course_id');
            $table->timestamps();
            $table->foreign('id')->references('id')->on('courses');
            $table->foreign('alpha_course_id')->references('id')->on('courses');
            $table->unique(['id','alpha_course_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('beta_courses');
    }
}
