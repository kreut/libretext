<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateForgeAssignmentQuestion extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('forge_assignment_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('adapt_question_id');
            $table->unsignedBigInteger('adapt_assignment_id');
            $table->string('forge_question_id');
            $table->string('forge_class_id');
            $table->timestamps();
            $table->foreign('adapt_assignment_id')
                ->references('id')
                ->on('assignments');
            $table->foreign('adapt_question_id')
                ->references('id')
                ->on('questions');
            $table->unique(['adapt_assignment_id', 'adapt_question_id', 'forge_question_id', 'forge_class_id'], 'adapt_forge_question_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('forge_assignment_questions');
    }
}
