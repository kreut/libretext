<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRubricCategorySubmissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rubric_category_submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rubric_category_id');
            $table->unsignedBigInteger('user_id');
            $table->text('submission');
            $table->string('status',10)->default('submitted');
            $table->text('message')->nullable();
            $table->text('customized_comments')->nullable();
            $table->unsignedSmallInteger('score')->nullable();
            $table->timestamps();
            $table->foreign('rubric_category_id')->references('id')->on('rubric_categories');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rubric_category_submissions');
    }
}
