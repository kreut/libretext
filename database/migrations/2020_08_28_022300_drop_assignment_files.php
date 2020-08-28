<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropAssignmentFiles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('assignment_files');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->unsignedBigInteger('assignment_id');
        $table->string('original_filename');
        $table->string('submission');
        $table->boolean('assignment_files')->default(0);
        $table->dateTime('date_submitted');
        $table->string('file_feedback')->nullable();
        $table->longText('text_feedback')->nullable();
        $table->dateTime('date_graded')->nullable();
        $table->unsignedTinyInteger('score')->nullable();
        $table->unique(['user_id', 'assignment_id']);
        $table->foreign('user_id')->references('id')->on('users');
        $table->foreign('assignment_id')->references('id')->on('assignments');


        $table->timestamps();
    }
}
