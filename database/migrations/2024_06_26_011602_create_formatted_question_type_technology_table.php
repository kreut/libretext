<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFormattedQuestionTypeTechnologyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('formatted_question_type_technology', function (Blueprint $table) {
            $table->id();
            $table->string('formatted_question_type');
            $table->string('technology');
            $table->timestamps();
            $table->unique(['formatted_question_type','technology'],'formatted_question_type_technology_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('formatted_question_type_technology');
    }
}
