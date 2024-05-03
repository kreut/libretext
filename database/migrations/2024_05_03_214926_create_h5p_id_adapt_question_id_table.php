<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateH5pIdAdaptQuestionIdTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('h5p_id_adapt_question_id', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('h5p_id');
            $table->string('email');
            $table->unsignedBigInteger('adapt_question_id')->nullable();
            $table->string('status')->nullable();
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
        Schema::dropIfExists('h5p_id_adapt_question_id');
    }
}
