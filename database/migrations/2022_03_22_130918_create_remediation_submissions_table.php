<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRemediationSubmissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('remediation_submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('assignment_id');
            $table->unsignedBigInteger('learning_tree_id');
            $table->unsignedTinyInteger('branch_id');
            $table->unsignedBigInteger('question_id');

            $table->unique(['user_id', 'assignment_id', 'learning_tree_id', 'branch_id', 'question_id'], 'remediation_submission_unique');
            $table->text('submission')->nullable();
            $table->decimal('proportion_correct', 8, 4)->nullable();
            $table->unsignedSmallInteger('submission_count');
            $table->timestamps();


            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('learning_tree_id')->references('id')->on('learning_trees');
            $table->foreign('assignment_id')->references('id')->on('assignments');
            $table->foreign('question_id')->references('id')->on('questions');

        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('remediation_submissions');
    }
}
