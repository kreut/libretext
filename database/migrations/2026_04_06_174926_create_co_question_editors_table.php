<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCoQuestionEditorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('co_question_editors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('question_editor_user_id');
            $table->unsignedBigInteger('co_question_editor_user_id');
            $table->string('access_code')->nullable();
            $table->string('status');
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
        Schema::dropIfExists('co_question_editors');
    }
}
