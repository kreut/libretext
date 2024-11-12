<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmptyParagraphQuestionMediaUploadFixesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('empty_paragraph_question_media_upload_fixes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('question_media_upload_id');
            $table->text('text');
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
        Schema::dropIfExists('empty_paragraph_question_media_upload_fixes');
    }
}
