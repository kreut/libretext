<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTesterStudentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tester_students', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tester_user_id');
            $table->unsignedBigInteger('student_user_id');
            $table->unsignedBigInteger('section_id');
            $table->timestamps();
            $table->foreign('tester_user_id')->references('id')->on('users');
            $table->foreign('student_user_id')->references('id')->on('users');
            $table->foreign('section_id')->references('id')->on('sections');
            $table->unique(['tester_user_id','student_user_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tester_students');
    }
}
