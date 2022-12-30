<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWebworkAttachmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('webwork_attachments', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->unsignedBigInteger('question_id');
            $table->timestamps();
            $table->foreign('question_id')->references('id')->on('questions');
            $table->unique(['filename','question_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('webwork_attachments');
    }
}
