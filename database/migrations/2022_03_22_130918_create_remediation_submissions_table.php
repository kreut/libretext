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
            $table->unsignedBigInteger('question_id');
            $table->text('submission')->nullable();
            $table->unsignedSmallInteger('time_spent')->nullable();
            $table->decimal('proportion_correct', 8, 4);
            $table->unsignedSmallInteger('submission_count');
            $table->timestamps();

            $table->unique(['user_id','assignment_id','question_id'], 'remediation_submission_unique');
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
