<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubmissionFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('submission_files', function (Blueprint $table) {
            $table->id();
            $table->enum('type',['q','a']);
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('assignment_id');
            $table->unsignedBigInteger('question_id')->nullable();
            $table->string('original_filename');
            $table->string('submission_file');
            $table->dateTime('date_submitted');
            $table->string('file_feedback')->nullable();
            $table->longText('text_feedback')->nullable();
            $table->dateTime('date_graded')->nullable();
            $table->unsignedTinyInteger('score')->nullable();

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('assignment_id')->references('id')->on('assignments');
            //can't have the unqiueness because the question_id might not exist
            //can't have the foreign key of the question_id because it might not exist


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('submission_files');
    }
}
